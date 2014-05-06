<?php

namespace Penn\CosignSSOBundle\Security\Authentication\Provider;

use Symfony\Component\HttpFoundation\Session\Session;

Class CosignUtil {
    
    private $session;
    
    public function __construct(Session $session) {
        $this->session = $session;
    }
    
    public function expire() {
        return Cosign::expire();
    }
    
    public function globalLogoutUrl() {
        return Cosign::globalLogoutUrl();
    }
}