<?php

namespace Penn\OAuth2TokenBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


class DefaultController extends Controller {

    
    /**
     * @Route("/", name="tokenIndex")
     * @Template()
     */
    public function indexAction() {
    
        $user = $this->getUser();
        $adminClient = $this->get('google_admin_client');

        if ( !$adminClient->isAccessTokenValid() ) {
            $adminClient->refreshToken(false);
        }
        
        
    	if ( $adminClient->isAccessTokenValid() ) {
    	    
    	    $customer_id = $adminClient->getCustomerId();
    	    $token_status = "VALID";
    	    	
    	} else {
    	    $token_status = "INVALID";
    	    $customer_id  = "(set token first)";
    	}

	    // generate URL for user authorization
    	$oauth_auth_url = $adminClient->createAuthUrl();
         
         
        //     	$orgs = $service->orgunits->listOrgunits($customer_id);
        //     	print_r($orgs);
         
        return array('oauth_auth_url'   => $oauth_auth_url,
                     'token_status'     => $token_status,
                     'customer_id'      => $customer_id,
                     'oauth_params'     => $adminClient->getOAuthParams(),
                     'refresh_token'    => $adminClient->getStorage()->getRefreshToken());
    }    
    
    
    /**
     * @Route("/oauth2callback", name="oauth2callback")
     * @Template()
     */    
    public function oauth2CallbackAction(Request $request) {
    	
        $user = $this->getUser();
    	$code = $request->query->get('code');
    	
        $adminClient = $this->get('google_admin_client');
        $adminClient->authenticate($code, $user->getUsername());
    	
    	return array();
    }
    
    
    /**
     * @Route("/revokeRefreshToken", name="revokeRefreshToken")
     * @Template()
     */
    public function revokeRefreshTokenAction() {
         
        $adminClient = $this->get('google_admin_client');
        $adminClient->revokeRefreshToken();
         
        return array();
         
    }    
}
