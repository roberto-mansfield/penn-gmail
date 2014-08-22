<?php

namespace SAS\IRAD\GMailConfigureBundle\Service;

use SAS\IRAD\GoogleAdminClientBundle\Service\GoogleAdminClient;
use SAS\IRAD\PennGroupsBundle\Service\PennGroupsQueryCache;
use SAS\IRAD\PersonInfoBundle\PersonInfo\PersonInfoInterface;
use SAS\IRAD\MailForwardingBundle\AuthCheck\AuthCheckService;
use Symfony\Component\Security\Core\SecurityContext;
use Swift_Mailer;

class UserHelper {
    
    private $googleAdmin;
    private $securityContext;
    private $penngroups;
    private $mailer;
    private $authCheck;
    private $google_params;
    
    public function __construct(GoogleAdminClient $googleAdmin, 
                                PennGroupsQueryCache $penngroups, 
                                AuthCheckService $authCheck,
                                SecurityContext $securityContext, 
                                Swift_Mailer $mailer,
                                array $google_params) {
        
        $this->googleAdmin = $googleAdmin;
        $this->securityContext = $securityContext;
        $this->penngroups  = $penngroups;
        $this->mailer = $mailer;
        $this->authCheck = $authCheck;
        $this->google_params = $google_params;
    }

    public function getPennEmail() {
        $pennkey = $this->getUsername();
        return $this->googleAdmin->getUserId($pennkey);
    }    
    
    public function getUsername() {
        return $this->securityContext->getToken()->getUser()->getUsername();
    }

    public function getPersonInfo() {
        $pennkey = $this->getUsername();
        return $this->penngroups->findByPennkey($pennkey);
    }
    
    public function getGoogleUser() {
        return $this->googleAdmin->getGoogleUser($this->getPersonInfo());
    }

    public function getAuthCheck() {
        return $this->authCheck->authCheck($this->getPersonInfo());
    }    
    
    public function userIsForwardingEligible() {
        $auth = $this->getAuthCheck();
        if ( $auth ) {
            return ( $auth->getValue('forwarding_eligible') === 'yes' );
        }
        return false;
    }
}