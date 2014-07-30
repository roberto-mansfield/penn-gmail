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
        
        $penngroups = $this->get('penngroups.query_cache');
        $gmail      = $this->get('google_admin_client');
        $forwarding = $this->get('mail_forwarding_service');
        
        $pennkey    = $this->getUser()->getUsername();
        $personInfo = $penngroups->findByPennkey($pennkey);
        
        // does the Google account exist?
        $googleUser = $gmail->getGoogleUser($personInfo);
        
        // get forwarding info
        $email    = $this->userEmail();
        $forwards = $forwarding->getForwarding($email);
        $forward_type = $forwarding->getForwardingType($forwards);

        // setup forwarding form
        $form = $this->createForm(new MailForwardingType($forwarding->getMaxForwards()));

        return array('email'            => $email,
                     'forwards'         => $forwards,
                     'forwardingType'   => $forward_type,
                     'gmail'            => $gmail,
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
    
        $penngroups = $this->get('penngroups.query_cache');
        $gmail      = $this->get('google_admin_client');
        $forwarding = $this->get('mail_forwarding_service');
    
        $pennkey    = $this->getUser()->getUsername();
        $personInfo = $penngroups->findByPennkey($pennkey);
    
        // does the Google account exist?
        $googleUser = $gmail->getGoogleUser($personInfo);
    
        // get forwarding info
        $email    = $this->userEmail();
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
        return array('email' => $this->userEmail());
    }
    
    
    public function userEmail() {
        $pennkey = $this->getUser()->getUsername();
        $googleParams = $this->container->getParameter('google_params');
        return $pennkey . '@' . $googleParams['domain'];
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
