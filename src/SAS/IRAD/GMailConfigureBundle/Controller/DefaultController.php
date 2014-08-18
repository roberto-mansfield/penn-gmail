<?php

namespace SAS\IRAD\GMailConfigureBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SAS\IRAD\GoogleAdminClientBundle\Form\Type\AccountFormType;
use SAS\IRAD\GoogleAdminClientBundle\Form\Type\GoogleNameType;
use SAS\IRAD\PersonInfoBundle\PersonInfo\PersonInfoInterface;


class DefaultController extends Controller {
    
    /**
     * @Route("/", name="gmailIndex")
     * @Template()
     */
    public function indexAction() {
        
        $helper = $this->get('user_helper');
        $gmail  = $this->get('google_admin_client');
        
        $personInfo = $helper->getPersonInfo();

        // does the account exist? for the index page, use a try/catch
        // so we can trap any exceptions and notify admins of problems
        try {
            $user = $gmail->getGoogleUser($personInfo);
            
        } catch (\Exception $e ) {
            $this->reportProblem($personInfo, $e);
            return $this->redirect($this->generateUrl("serverError"));
        }
        
        if ( !$user ) {
            // account hasn't been provisioned yet
            return $this->redirect($this->generateUrl("gmailAccountDoesNotExist"));
        }
        
        // when was the account created (force 24hr delay before user can access acount
        if ( $user->isAccountPending() ) {
            // redir to "wait" message
            return $this->redirect($this->generateUrl("gmailAccountPending"));
        }
        
        // are we initializing an account or resetting a password?
        if ( $user->isActivated() ) {
            // account alread active
            $action = 'reset-password';
        } else {
            // is account creation currently available?
            if ( !$gmail->isAccountCreationAvailable() ) {
                return $this->forward("GMailConfigureBundle:Default:gmailAccountCreationDisabled");
            }
            $action = 'create-account';
        }
        
        $accountForm = $this->createForm(new AccountFormType());
        
        return array('pennEmail'   => $helper->getPennEmail(),
                     'user'        => $user,
                     'action'      => $action,
                     'accountForm' => $accountForm->createView());
    }
    
    /**
     * @Route("/edit-name", name="editName")
     * @Template()
     */
    public function editNameAction(Request $request) {

        $helper = $this->get('user_helper');
        $user   = $helper->getGoogleUser();
        
        $form = $this->createForm(new GoogleNameType());
        $form->handleRequest($request);
        
        if ( $request->getMethod() == 'POST' && $form->isValid() ) {
                
            $params = $request->request->get('GoogleName');
            $user->setName($params);
            $user->commit();

            // takes a few seconds to update (or we can update local cache)
            sleep(3);
            
            return $this->redirect($this->generateUrl("gmailIndex"));
        }
        
        return array('user'     => $user,
                     'form'     => $form->createView());
    }

    
    /**
     * @Route("/invalid-account", name="gmailAccountDoesNotExist")
     * @Template()
     */
    public function gmailAccountDoesNotExistAction() {
        return array();
    }
    

    /**
     * @Route("/server-error", name="serverError")
     * @Template()
     */
    public function serverErrorAction() {
        return array();
    }
    
    
    /**
     * @Route("/account-pending", name="gmailAccountPending")
     * @Template()
     */
    public function gmailAccountPendingAction() {

        $helper = $this->get('user_helper');
        $user   = $helper->getGoogleUser();
                
        if ( !$user->isAccountPending() ) {
            return $this->redirect($this->generateUrl("gmailIndex"));
        }
        
        return array('user' => $user);
    }


    /**
     * @Route("/account-creation-disabled", name="gmailAccountCreationDisabled")
     * @Template()
     */
    public function gmailAccountCreationDisabledAction() {
        return array();
    }    
    
    
    /**
     * @Route("/ajax/activate-account", name="activateAccount")
     */
    public function activateAccountAction() {
    
        $helper = $this->get('user_helper');
        $gmail  = $this->get('google_admin_client');
        $user   = $helper->getGoogleUser();

        // is account creation currently available?
        if ( !$gmail->isAccountCreationAvailable() ) {
            return $this->forward("GMailConfigureBundle:Default:gmailAccountCreationDisabled");
        }        
        
        if ( !$user ) {
            // account hasn't been provisioned yet
            return $this->jsonError("Invalid account");
        }
    
        // when was the account created (force 24hr delay before user can access acount
        if ( $user->isAccountPending() ) {
            return $this->jsonError("Account is pending");
        }
    
        // is the account initialized?
        if ( $user->isActivated() ) {
            return $this->jsonError("Account already setup");
        }
    
        $request = $this->getRequest();
    
        if ( $request->getMethod() != 'POST') {
            return $this->jsonError("Invalid method");
        }
    
        $accountForm = $this->createForm(new AccountFormType());
        $accountForm->handleRequest($request);
        $params = $request->request->get("AccountForm");
    
        if ( !$accountForm->isValid() ) {
            return $this->jsonError("Invalid form");
        }
    
        try {
            $user->activateAccount(sha1($params['password1']));
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return $this->jsonError("An error occurred while provisioning your Google account");
        }
        
        if ( isset($params['mail_forwarding']) && $params['mail_forwarding'] == 'YES' ) {
            
            $forwarding = $this->get('mail_forwarding_service');
            
            try {
                $forwarding->setGmailForwarding($user->getPersonInfo());
            } catch (\Exception $e) {
                error_log("forwarding: " . $e->getMessage());
                return $this->jsonError("An error occurred while setting up your mail forwarding");
            }
        }
        
        return $this->json(
                array("result"  => "OK",
                      "message" => "Account activated",
                      "content" => $this->renderView("GMailConfigureBundle:Default:activateAccountSuccess.html.twig",
                                    array("pennEmail"      => $helper->getPennEmail(),
                                          "mailForwarding" => true))
        ));
    }    
    
    
    
    /**
     * @Route("/ajax/reset-password", name="resetPassword")
     */
    public function resetPasswordAction() {

        $helper = $this->get('user_helper');
        $user   = $helper->getGoogleUser();
        
        if ( !$user ) {
            // account hasn't been provisioned yet
            return $this->jsonError("Invalid account");
        }
        
        // when was the account created (force 24hr delay before user can access acount
        if ( $user->isAccountPending() ) {
            return $this->jsonError("Account is pending");
        }
        
        // is the account initialized?
        if ( !$user->isActivated() ) {
            return $this->jsonError("Uninitialized account");
        }
        
        $request = $this->getRequest();
        
        if ( $request->getMethod() != 'POST') {
            return $this->jsonError("Invalid method");
        }      
        
        $accountForm = $this->createForm(new AccountFormType());
        $accountForm->handleRequest($request);
        $params = $request->request->get("AccountForm");
        
        if ( !$accountForm->isValid() ) {
            return $this->jsonError("Invalid form");
        }
        
        try {
            $user->setPassword(sha1($params['password1']));
            $user->commit();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return $this->jsonError("An error occurred while attempting to reset your password");
        }
        
        return $this->json(array("result"  => "OK", 
                                 "message" => "Password reset complete",
                                 "content" => $this->renderView("GMailConfigureBundle:Default:passwordResetSuccess.html.twig",
                                                    array("pennEmail" => $helper->getPennEmail()))
                ));
    }

    
    
    private function jsonError($message) {
        return $this->json(array("result" => "ERROR", "message" => $message));
    }
    
    /**
     * Return a JSON response from the controller
     * @param array $array
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function json($array) {
        $response = new JsonResponse();
        $response->setData($array);
        return $response;
    }
    
    
    private function reportProblem(PersonInfoInterface $personInfo, \Exception $e) {
    
        // log to web server error log
        error_log($e->getMessage());
    
        // notify configured admins
        $params = $this->container->getParameter('google_params');
    
        if ( isset($params['report_errors_to']) ) {
    
            $message = \Swift_Message::newInstance()
            ->setSubject('Student Mail Provisioning Error')
            ->setTo($params['report_errors_to'])
            ->setFrom('apache@' . $_SERVER['HTTP_HOST'])
            ->setBody(
                    $this->renderView('GMailConfigureBundle:Default:reportErrorEmail.txt.twig',
                            array('personInfo' => $personInfo,
                                    'error'      => $e->getMessage()))
            );
    
            $this->get('mailer')->send($message);
        }
    }

} 