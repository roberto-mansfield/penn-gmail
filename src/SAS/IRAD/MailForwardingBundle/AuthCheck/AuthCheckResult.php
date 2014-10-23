<?php
/**
 * Convert the raw output from auth check command into a usable object
 * @author robertom
 */

namespace SAS\IRAD\MailForwardingBundle\AuthCheck;


class AuthCheckResult {
    
    private $data;
    
    public function __construct($data) {

        $this->data = array();
        
        foreach ( explode("\n", $data) as $line ) {
            
            if ( strpos($line, '=') === false ) {
                continue;
            }
            
            list($key, $value) = explode('=', $line);
        
            $key   = trim($key);
            $value = trim($value);
        
            // some keys can be multivalued
            if ( isset($this->data[$key]) ) {
                if ( !is_array($this->data[$key]) ) {
                    $this->data[$key] = array($this->data[$key]);
                }
                array_push($this->data[$key], $value);
            } else {
                $this->data[$key] = $value;
            }
        }
    }
    
    
    public function getValue($key) {
        if ( isset($this->data[$key]) ) {
            return $this->data[$key];
        }
        return false;
    }
    
    public function getData() {
        return $this->data;
    }
}

