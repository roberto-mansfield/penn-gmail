<?php

namespace Penn\CosignSSOBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session\Session;


class CosignUserProvider implements UserProviderInterface {
    
    private $admin_users;
    
    public function __construct($admin_users) {
        $this->admin_users = $admin_users;
    }
    
    public function loadUserByUsername($username) {
        return new CosignUser($username, $this->admin_users);
    }
    
    public function refreshUser(UserInterface $user) {
        return $this->loadUserByUsername($user->getUsername());
    }
    
    public function supportsClass($class) {
        return $class === 'namespace Penn\CosignSSOBundle\Security\CosignUser';
    }
    
}