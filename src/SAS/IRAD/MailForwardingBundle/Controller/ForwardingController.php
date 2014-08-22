<?php

namespace SAS\IRAD\MailForwardingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SAS\IRAD\MailForwardingBundle\Form\Type\MailForwardingType;


class ForwardingController extends Controller {

    /**
     * @Route("/", name="forwardingIndex")
     * @Template()
     */
    public function indexAction() {
        
        $helper     = $this->get('user_helper');
        $gmail      = $this->get('google_admin_client');
        $forwarding = $this->get('mail_forwarding_service');
        
        $personInfo = $helper->getPersonInfo();
        
        try {
            $eligible = $helper->userIsForwardingEligible();
        } catch (\Exception $e) {
            return $this->redirect($this->generateUrl("authCheckError"));
        }
        
        if ( !$eligible ) {
            return $this->redirect($this->generateUrl("forwardingIneligible"));
        }
        
        // does the Google account exist?
        $googleUser = $helper->getGoogleUser();
        
        // get forwarding info
        $email    = $helper->getPennEmail();
        $forwards = $forwarding->getForwarding($email);
        $forward_type = $forwarding->getForwardingType($forwards);

        // setup forwarding form
        $form = $this->createForm(new MailForwardingType($forwarding->getMaxForwards()));

        return array('email'            => $email,
                     'forwards'         => $forwards,
                     'forwardingType'   => $forward_type,
                     'accountCreationAvailable' => $gmail->isAccountCreationAvailable(),
                     'googleUser'       => $googleUser,
                     'form'             => $form->createView(),
                     'maxForwards'      => $forwarding->getMaxForwards(),
                     'equivDomains'     => $forwarding->getEquivalentDomains());
    }
    
    
    /**
     * @Route("/update-forwarding", name="updateForwarding")
     * @Template()
     */
    public function updateForwardingAction(Request $request) {
    
        $helper     = $this->get('user_helper');
        $forwarding = $this->get('mail_forwarding_service');
        $personInfo = $helper->getPersonInfo();
    
        // does the Google account exist?
        $googleUser = $helper->getGoogleUser();
    
        // get forwarding info
        $email    = $helper->getPennEmail();
        $forwards = $forwarding->getForwarding($email);
        $forward_type = $forwarding->getForwardingType($forwards);
    
        // setup forwarding form
        $form = $this->createForm(new MailForwardingType($forwarding->getMaxForwards()));
        $form->handleRequest($request);
        
        $response = array();
        
        if ( $request->getMethod() == 'POST' && $form->isValid() ) {
        
            $params = $request->request->get('MailForwarding');
            $newForwards = array();
            
            // additional validation
            if ( $params['forwarding_type'] == 'pennlive' && $forward_type != 'pennlive' ) {
                return $this->jsonError("Unsupported option for update");
            }
            
            // if user selects "other" for mail forwarding, validate entered email addresses
            if ( $params['forwarding_type'] == 'other' ) {

                foreach ( $params['forwarding_address'] as $address ) {
                    if ( $forwarding->isAddressValid($address) && !in_array($address, $newForwards)) {
                        $newForwards[] = $address;
                    }
                }
                
                if ( count($newForwards) == 0 ) {
                    return $this->jsonError("No valid email addresses found");
                } 
                
                $forwarding->setForwarding($personInfo, $newForwards);
                
            } elseif ( $params['forwarding_type'] == 'gmail' ) {
                $forwarding->setGmailForwarding($personInfo);
            }

            // setup params for template render
            $template = array('email'    => $email,
                              'forwards' => $newForwards,
                              'forwardingType' => $params['forwarding_type']);
            
            $response['result']  = 'OK';
            $response['content'] = $this->renderView("MailForwardingBundle:Forwarding:success.html.twig", $template);

            return $this->json($response);
            
        }  

        return $this->jsonError("Invalid data");
    }
    
    
    /**
     * @Route("/help", name="help")
     * @Template()
     */
    public function helpAction() {
        $helper = $this->get('user_helper');
        return array('email' => $helper->getPennEmail());
    }

    
    /**
     * @Route("/ineligible", name="forwardingIneligible")
     * @Template()
     */
    public function forwardingIneligibleAction() {
        
        $helper     = $this->get('user_helper');
        $auth_check = $helper->getAuthCheck();
        
        if ( $auth_check->getValue('facstaff_exists') == 'yes' ) {
            $link  = 'https://www.sas.upenn.edu/facstaff/account/';
            $label = 'Configure my FacStaff account';
        
        } else {
            $link  = 'http://www.sas.upenn.edu/computing/help/';
            $label = 'View the SAS Computing FAQ pages';
        }
        
        return compact('link', 'label');
    }
    
    
    /**
     * @Route("/authCheckError", name="authCheckError")
     * @Template()
     */
    public function authCheckErrorAction() {
        return array();
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
