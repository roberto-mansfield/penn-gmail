<?php

namespace Penn\CosignSSOBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;


class CosignUser implements UserInterface {

    private $username;
    private $password;
    private $salt;
    private $roles;
    
    public function __construct($username, $admin_users) {

        $this->username = $username;
        $this->roles = array();
        
        foreach ( $admin_users as $role => $users ) {
            if ( in_array($this->username, $users) ) {
                $this->roles[] = $role;
            }
        }
    }
    
    /**
     * Required methods for interface
     * @see Symfony\Component\Security\Core\User.UserInterface::getRoles()
     */
    public function getRoles() {
        return $this->roles;
    }
    
    public function getPassword() {
        return $this->password;
    }
    
    public function getSalt() {
        return $this->salt;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function eraseCredentials() {
    }
    
    public function equals(UserInterface $user) {
        return ( $this->username === $user->getUsername() );
    }
    
    
}