<?php

namespace SAS\IRAD\GMailConfigureBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SAS\IRAD\GoogleAdminClientBundle\Form\Type\AccountFormType;
use SAS\IRAD\GoogleAdminClientBundle\Form\Type\GoogleNameType;


class DefaultController extends Controller {
    
    /**
     * @Route("/", name="gmailIndex")
     * @Template()
     */
    public function indexAction() {
        
        $service = $this->get('penngroups.query_cache');
        $gmail   = $this->get('google_admin_client');
        
        $pennkey    = $this->getUser()->getUsername();
        $personInfo = $service->findByPennkey($pennkey);

        // does the account exist?
        try {
            $user = $gmail->getGoogleUser($personInfo);
            
        } catch (\Exception $e ) {
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
            $action = 'create-account';
        }
        
        $accountForm = $this->createForm(new AccountFormType());
        
        return array('user'        => $user,
                     'action'      => $action,
                     'accountForm' => $accountForm->createView());
    }
    
    /**
     * @Route("/edit-name", name="editName")
     * @Template()
     */
    public function editNameAction(Request $request) {

        $service = $this->get('penngroups.query_cache');
        $gmail   = $this->get('google_admin_client');
        
        $pennkey    = $this->getUser()->getUsername();
        $personInfo = $service->findByPennkey($pennkey);
        
        $user = $gmail->getGoogleUser($personInfo);
        
        $form = $this->createForm(new GoogleNameType());
        $form->handleRequest($request);
        
        if ( $request->getMethod() == 'POST' && $form->isValid() ) {
                
            $params = $request->request->get('GoogleName');
            $user->setName($params);

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
     * @Route("/account-pending", name="gmailAccountPending")
     * @Template()
     */
    public function gmailAccountPendingAction() {
        
        $gmail   = $this->get('google_admin_client');
        $pennkey = $this->getUser()->getUsername();
        $user    = $gmail->getGoogleUser($pennkey);
        
        if ( !$user->isAccountPending() ) {
            return $this->redirect($this->generateUrl("gmailIndex"));
        }
        
        $pending = $user->getCreationTime() + 86400 - time();
        
        return array('pending' => $pending);
    }
    
    
    /**
     * @Route("/ajax/reset-password", name="resetPassword")
     */
    public function resetPasswordAction() {

        $service = $this->get('penngroups.query_cache');
        $gmail   = $this->get('google_admin_client');
        
        $pennkey    = $this->getUser()->getUsername();
        $personInfo = $service->findByPennkey($pennkey);

        // does the account exist?
        try {
            $user = $gmail->getGoogleUser($personInfo);
        
        } catch (\Exception $e ) {
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
        
        $user->setPassword($params['password1']);
        
        return $this->json(array("result"  => "OK", 
                                 "message" => "Password reset complete",
                                 "content" => $this->renderView("GMailConfigureBundle:Default:passwordResetSuccess.html.twig",
                                                    array("userId" => $user->getUserId()))
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
}