<?php

namespace SAS\IRAD\GMailConfigureBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Penn\GoogleAdminClientBundle\Form\Type\GoogleNameType;


class DefaultController extends Controller {
    
    /**
     * @Route("/", name="gmailIndex")
     * @Template()
     */
    public function indexAction() {
        
        $service = $this->get('person_info_cache');
        $gmail   = $this->get('google_admin_client');
        
        $pennkey    = $this->getUser()->getUsername();
        $personInfo = $service->searchByPennkey($pennkey);

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
        if ( $user->getOrgUnitPath() == '/bulk-created-accounts' ) {
            $action = 'create-account';
        } else {
            // account alread active
            $action = 'reset-password';
        }
        
        return array('user'       => $user,
                     'action'     => $action,
                     'step'       => 'instructions');
    }
    
    /**
     * @Route("/edit-name", name="editName")
     * @Template()
     */
    public function editNameAction(Request $request) {

        $service = $this->get('person_info_cache');
        $gmail   = $this->get('google_admin_client');
        
        $pennkey    = $this->getUser()->getUsername();
        $personInfo = $service->searchByPennkey($pennkey);
        
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
}