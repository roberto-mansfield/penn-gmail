<?php

namespace SAS\IRAD\PersonInfoBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;


/**
 * This is basically a wrapper around PersonInfoClient, but we use the session
 * to cache information. Adjust cache lifetime with person_info.cache_timeout in 
 * parameters.yml
 * 
 * @author robertom
 */

class PersonInfoCache {
    
    private $client;
    private $session;
    private $cache_timeout;
    
    public function __construct(PersonInfoClient $client, Session $session, $params) {
        $this->client  = $client;
        $this->session = $session;
        $this->cache_timeout = $params['cache_timeout'];
    }
    
    public function searchByPennkey($pennkey) {
        return $this->search('searchByPennkey', "person_info/pennkey/$pennkey", $pennkey);
    }
    
    public function searchByPennID($penn_id) {
        return $this->search('searchByPennID', "person_info/penn_id/$penn_id", $penn_id);
    }    
    
    
    private function search($method, $key, $arg) {
        
        if ( $cache = $this->session->get($key) ) {
            if ( $cache['expires_on'] > time() ) {
                return $cache['data'];
            }
        }

        $data = $this->client->$method($arg);
        $info = array('expires_on' => time() + $this->cache_timeout,
                      'data'       => $data);
        
        $this->session->set($key, $info);
        
        return $data;
    }
    
}