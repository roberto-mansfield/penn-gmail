<?php

namespace Penn\OAuth2TokenBundle\Service;

use Penn\OAuth2TokenBundle\Storage\FileStorage;


class OAuth2TokenStorage {
    
    private $refreshTokenStorage;
    private $accessTokenStorage;
    
    /**
     * Constructor
     * @param array $oauth_params defined in app/config/parameters.yml
     */
    public function __construct($oauth_params) {
        
        foreach ( array('refresh_token_file', 'access_token_file') as $param ) {
            if ( !isset($oauth_params[$param]) ) {
                throw new \Exception("Parameter oauth_params.$param must be defined.");
            }
        }
        
        $this->refreshTokenStorage = new FileStorage($oauth_params['refresh_token_file']);
        $this->accessTokenStorage  = new FileStorage($oauth_params['access_token_file']);
    }

    /**
     * Save the access token as a string (already in json format)
     * @param string $data
     */
    public function saveAccessToken($data) {
        $this->accessTokenStorage->save($data);
    }
    
    /**
     * Encode refresh token data as json for storage
     * @param array $data
     */
    public function saveRefreshToken($data) {
        $this->refreshTokenStorage->save(json_encode($data));
    }

    /**
     * Return the access token json string
     * @return string
     */
    public function getAccessToken() {
        $tokenString = $this->accessTokenStorage->get();
        if ( !$tokenString ) {
            // return an "emtpy" access token
            $tokenString = json_encode(array(
               'access_token' => '',
               'expires_in'   => 0,
               'created'      => 0,
            ));
        }
        return $tokenString;
    }
    
    /**
     * Return array of info for the refresh token
     * @return array
     */
    public function getRefreshToken() {
        return json_decode($this->refreshTokenStorage->get(), true);
    }
    
    public function deleteRefreshToken() {
        $this->refreshTokenStorage->delete();
    }
}