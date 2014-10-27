<?php

namespace SAS\IRAD\UserInfoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


class UserInfoController extends Controller {
    
    /**
     * @Route("/{search_term}", name="userInfoIndex", defaults={"search_term" = false})
     * @Template()
     */
    public function indexAction($search_term) {
        
        $lookup     = $this->get("penngroups.web_service_query");
        $forwarding = $this->get('mail_forwarding_service');
        $authcheck  = $this->get('auth_check_service');
        $gadmin     = $this->get('google_admin_client');
        
        if ( preg_match('/^\d{8}$/', $search_term) ) {
            $penn_id  = $search_term;
            $userinfo = $lookup->findByPennId($penn_id);
        } elseif ( preg_match('/^[a-z][a-z0-9]{2,16}$/', strtolower($search_term)) ) {
            $pennkey  = $search_term;
            $userinfo = $lookup->findByPennkey($pennkey);
        } else {
            $search_term = false;
            return array('search_term' => $search_term);
        }
        
        $result = array('search_term' => $search_term,
                        'userinfo'    => $userinfo);
        
        // is the pennkey/penn_id valid?
        if ( $userinfo ) {
            // get mail forwarding
            $email    = $gadmin->getUserId($userinfo->getPennkey());
            $result['forwards'] = $forwarding->getForwarding($email);
            
            // get account info
            $result['chkInfo']  = $authcheck->authCheck($userinfo);
        
            // get account logs
            $repo = $this->getDoctrine()->getRepository("GmailAccountLogBundle:AccountLog");
            $result['entries']  = $repo->getBySearchTerm($userinfo->getPennId());
        }
        
        return $result;
    }
}
