<?php

namespace Penn\CosignSSOBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Penn\CosignSSOBundle\Security\Authentication\Token\CosignSSOUserToken;
use Penn\CosignSSOBundle\Security\Authentication\Provider\Cosign;

class CosignSSOProvider implements AuthenticationProviderInterface {
    
    private $cosign;
    private $cosignApp;
    private $userProvider;
    private $session;

    public function __construct(Session $session, UserProviderInterface $userProvider, $cosignApp) {
        $this->userProvider = $userProvider;
        $this->cosignApp    = $cosignApp;
        $this->cosign       = new Cosign($this->cosignApp);
        $this->session      = $session;
    }

    public function authenticate(TokenInterface $token) {

        // save the global cosign logout url to the session so we can get it in our controller
        $this->session->set('cosign/global_logout_url', Cosign::globalLogoutUrl());
        
        // load our user by cosign pennkey
        $user = $this->userProvider->loadUserByUsername($this->cosign->pennkey());

        if ( $user ) {

            $authenticatedToken = new CosignSSOUserToken($user->getRoles());
            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }

        throw new AuthenticationException('The Cosign SSO authentication failed.');
    }

    public function supports(TokenInterface $token) {
        return $token instanceof CosignSSOUserToken;
    }
    
    /**
     * Return the URL for global logout from Cosign
     */
    public function globalLogoutUrl() {
        return Cosign::globalLogoutUrl();
    }
    
    /**
     * Expire our cosign token.
     */
    public function logout() {
        $this->cosign->expire();
    } 
}
