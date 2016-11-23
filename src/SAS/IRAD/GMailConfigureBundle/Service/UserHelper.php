<?php

namespace SAS\IRAD\GMailConfigureBundle\Service;

use SAS\IRAD\GoogleAdminClientBundle\Service\GoogleAdminClient;
use SAS\IRAD\PennGroupsBundle\Service\PennGroupsQueryCache;
use SAS\IRAD\PersonInfoBundle\PersonInfo\PersonInfoInterface;
use SAS\IRAD\MailForwardingBundle\Service\MailForwardingService;
use Symfony\Component\Security\Core\SecurityContext;


class UserHelper {
    
    private $googleAdmin;
    private $gmailEligibility;
    private $securityContext;
    private $penngroups;
    private $forwarding;
    
    public function __construct(GoogleAdminClient $googleAdmin,
                                GmailEligibility $gmailEligibility,
                                PennGroupsQueryCache $penngroups, 
                                SecurityContext $securityContext, 
                                MailForwardingService $forwarding) {
        
        $this->googleAdmin = $googleAdmin;
        $this->gmailEligiblity = $gmailEligibility;
        $this->securityContext = $securityContext;
        $this->penngroups  = $penngroups;
        $this->forwarding = $forwarding;
    }

    public function getPennEmail(PersonInfoInterface $person = null) {
        if ( $person === null ) {
            $pennkey = $this->getUsername();
        } else {
            $pennkey = $person->getPennkey();
        }
        return $this->googleAdmin->getUserId($pennkey);
    }    
    
    public function getUsername() {
        return $this->securityContext->getToken()->getUser()->getUsername();
    }

    public function getPersonInfo($pennkey = false) {
        if ( $pennkey === false ) {
            $pennkey = $this->getUsername();
        }
        return $this->penngroups->findByPennkey($pennkey);
    }
    
    public function getGoogleUser(PersonInfoInterface $person = null) {
        if ( $person === null ) {
            $person = $this->getPersonInfo();
        }        
        return $this->googleAdmin->getGoogleUser($person);
    }

    public function userIsForwardingEligible(PersonInfoInterface $person = null) {

        if ( $person === null ) {
            $person = $this->getPersonInfo();
        }
        
        $eligible = $this->gmailEligiblity->isGmailEligible($person);
        
        // consider someone forwarding eligible if they have an existing forwarding entry
        $eligible = $eligible || ( count($this->forwarding->getForwarding($this->getPennEmail($person))) > 0 );

        return $eligible;
    }
}