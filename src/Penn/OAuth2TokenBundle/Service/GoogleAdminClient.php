<?php

namespace Penn\OAuth2TokenBundle\Service;

use Google_Client;
use Google_Service_Directory;
use Google_Auth_Exception;

class GoogleAdminClient {
    
    private $oauth_params;
    private $google_params;
    private $client;
    
    public function __construct($oauth_params, $google_params) {
        
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

        // check for file existence or writable files
        if ( file_exists($oauth_params['refresh_token_file']) ) {
            $this->oauth_params['refresh_token'] = json_decode(file_get_contents($oauth_params['refresh_token_file']), true);
        }
        
        if ( file_exists($oauth_params['token_cache_file']) ) {
            $access_token = file_get_contents($oauth_params['token_cache_file']);
            $this->client->setAccessToken($access_token);
        }
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
    public function getOauthParams() {
        return $this->oauth_params;
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
        if ( isset($this->oauth_params['refresh_token']) &&  
             isset($this->oauth_params['refresh_token']['token']) && 
             $this->oauth_params['refresh_token']['token']) {
            try {
                $this->client->refreshToken($this->oauth_params['refresh_token']['token']);
            } catch (Google_Auth_Exception $e) {
                if ( $required ) {
                    throw $e;
                }
            }
            // write new access token to cache file
            $this->storeFile($this->oauth_params['token_cache_file'], $this->client->getAccessToken());
        }
    }    

    /**
     * Revoke our current refresh token
     * @return url
     */
    public function revokeRefreshToken() {
        if ( isset($this->oauth_params['refresh_token']) &&  
             isset($this->oauth_params['refresh_token']['token']) && 
             $this->oauth_params['refresh_token']['token']) {
            $this->client->revokeToken($this->oauth_params['refresh_token']['token']);
            unlink($this->oauth_params['refresh_token_file']);
        }
    }    
    
    
    /**
     * Test if an access token is valid (i.e., not time out or revoked)
     * @return boolean
     */
    public function isAccessTokenValid() {
        
        $access_token = $this->client->getAccessToken();
        
        if ( !$access_token ) {
            return false;
        }
        
        if ( $this->client->isAccessTokenExpired() ) {
            return false;
        }
        
        // extract actual token from json string
        $access_token = json_decode($access_token, true);
        $token = $access_token['access_token'];
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,"https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=$token");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
         
        if ( !$token_info = json_decode($response, true) ) {
            throw new \Exception("Unable to decode tokeninfo response. Can't validate token");
        }

        // is this our token?
        if ( isset($token_info['issued_to']) && $token_info['issued_to'] == $this->oauth_params['client_id'] ) { 
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Validate a token returned from Google in OAuth2 callback. Resulting tokens are cached
     * @param string $code
     * @throws \Exception
     */
    
    public function authenticate($code) {
        
        try {
            $result = $this->client->authenticate($code);
        } catch (\Google_Auth_Exception $e) {
            throw new \Exception("Invalid code returned after OAuth2 authorization.");
        }
         
        $token_info = json_decode($this->client->getAccessToken());
         
        // store refresh token separately
        $refresh_token = array("token"      => $token_info->refresh_token,
                               "created_by" => "robertom",
                               "created_on" => $token_info->created);

        $this->storeFile($this->oauth_params['refresh_token_file'], json_encode($refresh_token));
        
        // store remainder of token in token cache
        unset($token_info->refresh_token);
        $this->storeFile($this->oauth_params['token_cache_file'], json_encode($token_info));
        
    }
    
    /**
     * Store the file contents and set file permissions, etc.
     * @param string $file
     * @param string $contents
     */
    private function storeFile($file, $contents) {
        if ( !file_exists($file) ) {
            touch($file);
        }
        chmod($file, 0640);
        file_put_contents($file, $contents);
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