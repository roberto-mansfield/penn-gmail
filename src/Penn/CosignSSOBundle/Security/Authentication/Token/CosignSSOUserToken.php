<?php

namespace Penn\CosignSSOBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class CosignSSOUserToken extends AbstractToken {

    public $created;

    public function __construct(array $roles = array()) {

        parent::__construct($roles);

        // User is redirected away if not authenticated. So with cosign, if
        // we are here, user is authenticated.
        $this->setAuthenticated(true);
    }

    public function getCredentials() {
        return '';
    }
    
    public function serialize() {
        return null;
    }
    
}
