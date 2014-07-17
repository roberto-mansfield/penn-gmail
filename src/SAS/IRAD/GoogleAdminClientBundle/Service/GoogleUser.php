<?php

namespace SAS\IRAD\GoogleAdminClientBundle\Service;

use Google_Auth_Exception;
use Google_Service_Directory_User_Resource;
use Google_Service_Directory_User;
use SAS\IRAD\GmailAccountLogBundle\Service\AccountLogger;

class GoogleUser {
    
    private $user_id;
    private $admin;
    private $user;
    private $logger;
    private $personInfo;
    
    public function __construct($user_id, Google_Service_Directory_user $user, PersonInfoInterface $personInfo, GoogleAdminClient $admin, AccountLogger $logger) {
        
        $this->user_id    = $user_id;
        $this->admin      = $admin;
        $this->user       = $user;
        $this->personInfo = $personInfo;
        $this->logger     = $logger;
    }
    
    /**
     * Set the first/last name on a Google account
     * @param array $name array("first_name" => $first, "last_name" => $last)
     * @throws \Exception
     */
    public function setName($name) {
        
        if ( !is_array($name) ) {
            throw new \Exception("GoogleUser::setName() expects array for input");
        }
        
        if ( !isset($name['first_name']) || !isset($name['last_name']) ) {
            throw new \Exception("Invalid array passed to GoogleUser::setName()");
        }
        
        // are we really changing anything?
        if ( $name['first_name'] != $this->getFirstName() || $name['last_name'] != $this->getLastName() ) {

            $this->user->getName()->setGivenName($name['first_name']);
            $this->user->getName()->setFamilyName($name['last_name']);

            $this->admin->updateGoogleUser($this);
            $this->logger->log($this->personInfo, 'UPDATE', 'GMail account first/last name updated.');
        }
    }
    
    /**
     * Set the password on a Google account
     * @param string $password
     * @throws \Exception
     */
    public function setPassword($password) {
        
        if ( !$password ) {
            throw new \Exception("GoogleUser::setPassword requires parameter for input");
        }
        
        $this->user->setPassword($password);
        $this->admin->updateGoogleUser($this);
        $this->logger->log($this->personInfo, 'UPDATE', 'GMail password reset.');
    }
    
    /**
     * Set org unit on google account
     * @param string $org_unit
     */
    public function setOrgUnit($org_unit) {
        $this->user->setOrgUnitPath("/$org_unit");
        $this->admin->updateGoogleUser($this);
        $this->logger->log($this->personInfo, 'UPDATE', "GMail account moved to OU=$org_unit.");
    }
    
    /**
     * Activate a Google account: set the password and move to "activated-accounts" OU
     * @param string $password
     */
    public function activateAccount($password) {

        if ( !$password ) {
            throw new \Exception("GoogleUser::activateAccount requires parameter for input");
        }
        
        $this->setPassword($password);
        $this->setOrgUnit('activated-accounts');
   }
    
    public function getFullName() {
        return $this->user->getName()->getFullName();
    }
    
    public function getFirstName() {
        return $this->user->getName()->getGivenName();
    }

    public function getLastName() {
        return $this->user->getName()->getFamilyName();
    }
    
    public function getUserId() {
        return $this->user_id;
    }
    
    public function getServiceDirectoryUser() {
        return $this->user;
    }
    
    public function getPersonInfo() {
        return $this->personInfo;
    }
    
    /**
     * Retrieve the account creation time from the Google directory user object
     * -- returned as string -- and convert to unix timestamp
     */
    public function getCreationTime() {
        return strtotime($this->user->getCreationTime());
    }
    
    public function getOrgUnitPath() {
        return $this->user->getOrgUnitPath();
    }

    public function isAccountPending() {
        return ( time() - $this->getCreationTime() < 86400 );
    }

    public function isPennIdHash() {
        $hashPennIdAccount = $this->admin->getUserId($this->admin->getPennIdHash($this->personInfo->getPennId()));
        return ( $this->user->getPrimaryEmail() == $hashPennIdAccount );
    }
    
    public function isActivated() {
        // TODO: Should org unit path be parameter?
        return ( $this->user->getOrgUnitPath() != "/bulk-created-accounts" );
    }
}