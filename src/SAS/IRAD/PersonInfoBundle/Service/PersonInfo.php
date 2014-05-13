<?php

namespace SAS\IRAD\PersonInfoBundle\Service;

use Penn\GoogleAdminClientBundle\Service\PersonInfoInterface;


class PersonInfo implements PersonInfoInterface {
    
    private $pennkey;
    private $penn_id;
    private $first_name;
    private $last_name;
    
    public function __construct($array) {
        
        foreach ( array('pennkey', 'penn_id', 'first_name', 'last_name') as $field ) {
            if ( isset($array[$field]) ) {
                $this->$field = $array[$field];
            }
        }
    }
    
    public function getPennkey() {
        return $this->pennkey;
    }
    
    public function getPennId() {
        return $this->penn_id;
    }

    public function getFirstName() {
        return $this->first_name;
    }    

    public function getLastName() {
        return $this->last_name;
    }
    
}