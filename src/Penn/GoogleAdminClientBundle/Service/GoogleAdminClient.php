<?php

namespace Penn\GoogleAdminClientBundle\Service;

use Google_Client;
use Google_Service_Directory;
use Google_Auth_Exception;
use Penn\OAuth2TokenBundle\Service\OAuth2TokenStorage;

class GoogleAdminClient {
    
    private $oauth_params;
    private $google_params;
    private $client;
    
    public function __construct(OAuth2TokenStorage $storage, $oauth_params, $google_params) {
        
        $this->oauth_params  = $oauth_params;
        $this->google_params = $google_params;

        $this->client = new Google_Client();
        
        // TODO: Check for existence of all params
        
        $this->client->setClientId($oauth_params['client_id']);
        $this->client->setClientSecret($oauth_params['client_secret']);
        $this->client->setRedirectUri($oauth_params['redirect_uri']);
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->client->addScope($oauth_params['scopes']);
        
        $this->storage = $storage;
        $this->client->setAccessToken($this->storage->getAccessToken());
    }
    
    /**
     * Return the Google_Client object
     * @return Google_Client
     */
    public function getClient() {
        return $this->client;
    }
    
    /**
     * Return the Google OAuth2 parameters
     * @return array`
     */
    public function getOAuthParams() {
        return $this->oauth_params;
    }
    
    /**
     * Return the Storage object
     * @return OAuth2TokenStorage
     */
    public function getStorage() {
        return $this->storage;
    }
    
    
    /**
     * Wrapper for Google_Client createAuthUrl() method
     * @return url
     */
    public function createAuthUrl() {
        return $this->client->createAuthUrl();
    }
    
    /**
     * Wrapper for Google_Client refreshToken() method. Pass arg $required=false if you don't 
     * want a refresh failure to throw an exception. E.g., in the token admin pages, an invalid
     * token is okay since we may be generating a new token. But in scripts and web ui calls,
     * a failure should stop everything. 
     * @param boolean $required
     * @return url
     */
    public function refreshToken($required=true) {
        
        $tokenInfo = $this->storage->getRefreshToken();
        
        if ( $tokenInfo && isset($tokenInfo['token']) && $tokenInfo['token'] ) {
                 
            try {
                $this->client->refreshToken($tokenInfo['token']);
            } catch (Google_Auth_Exception $e) {
                if ( $required ) {
                    throw $e;
                }
            }
            // write new access token to cache file
            $this->storage->saveAccessToken($this->client->getAccessToken());
        }
    }    

    /**
     * Revoke our current refresh token
     * @return url
     */
    public function revokeRefreshToken() {

        $tokenInfo = $this->storage->getRefreshToken();
        
        if ( $tokenInfo && isset($tokenInfo['token']) && $tokenInfo['token'] ) {
            $this->client->revokeToken($tokenInfo['token']);
            $this->storage->deleteRefreshToken();
        }
    }    
    
    
    /**
     * Test if an access token is valid (i.e., not time out or revoked)
     * @return boolean
     */
    public function isAccessTokenValid() {
        
        $accessToken = $this->client->getAccessToken();
        
        if ( !$accessToken ) {
            return false;
        }
        
        if ( $this->client->isAccessTokenExpired() ) {
            return false;
        }
        
        // extract actual token from json string
        $accessToken = json_decode($accessToken, true);
        $token = $accessToken['access_token'];
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,"https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=$token");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
         
        if ( !$tokenInfo = json_decode($response, true) ) {
            throw new \Exception("Unable to decode tokeninfo response. Can't validate token");
        }

        // is this our token?
        if ( isset($tokenInfo['issued_to']) && $tokenInfo['issued_to'] == $this->oauth_params['client_id'] ) { 
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Validate a token returned from Google in OAuth2 callback. Resulting tokens are cached
     * @param string $code     The authentication code returned by Google OAuth2 
     * @param string $username Log who generated this token
     * @throws \Exception
     */
    
    public function authenticate($code, $username) {
        
        try {
            $result = $this->client->authenticate($code);
        } catch (\Google_Auth_Exception $e) {
            throw new \Exception("Invalid code returned after OAuth2 authorization.");
        }
         
        $tokenInfo = json_decode($this->client->getAccessToken());
         
        // store refresh token separately
        $refreshToken = array("token"      => $tokenInfo->refresh_token,
                              "created_by" => $username,
                              "created_on" => $tokenInfo->created);

        $this->storage->saveRefreshToken($refreshToken);
        
        // store remainder of token in token cache
        unset($tokenInfo->refresh_token);
        $this->storage->saveAccessToken(json_encode($tokenInfo));
    }
    
    /**
     * Return a new Directory API service object
     * @return Google_Service_Directory
     */
    public function getDirectoryService() {
        return new Google_Service_Directory($this->client);
    }
    
    
    /**
     * Return the customer id associated with google_params.app_account. This value should be 
     * stored in google_params.customer_id, but we need a method so we can initially find the
     * value and display it in the admin token manager.
     */
    public function getCustomerId() {
        
        $service      = $this->getDirectoryService();
        $app_account  = $service->users->get($this->google_params['app_account']);
        $customer_id  = $app_account->getCustomerId();
    
        return $customer_id;
    }
}