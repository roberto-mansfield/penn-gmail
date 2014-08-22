<?php

namespace SAS\IRAD\MailForwardingBundle\AuthCheck;

use Symfony\Component\HttpFoundation\Session\Session;
use SAS\IRAD\PersonInfoBundle\PersonInfo\PersonInfoInterface;


class AuthCheckService {
    
    private $auth_script;
    private $session;
    
    public function __construct(Session $session, $params) {
        $this->session     = $session;
        $this->auth_script = $params['auth_script'];
    }
    
    
    /**
     * Run authcheck for given user (use one hour cache)
     * @param PersonInfoInterface
     * @return boolean
     */
    public function authCheck(PersonInfoInterface $personInfo) {
        
        $penn_id = $personInfo->getPennId();
        $pennkey = $personInfo->getPennkey();
        
        if ( $cache = $this->session->get("authCheck/$pennkey/$penn_id") ) {
            if ( $cache['expires_on'] > time() ) {
                return $cache['data'];
            }
        }
            
        $cmd  = "{$this->auth_script} $pennkey $penn_id";
        $data = shell_exec( escapeshellcmd($cmd) );

        $result = new AuthCheckResult($data);
        
        ## did ssh return a failure
        if ( $result->getValue('ssh_script_run') != 'success' ) {
            error_log("Error running $cmd to get account details");
            throw new \Exception("Error running $cmd to get account details");
        }        
        
        $info = array('expires_on' => time() + 3600,
                      'data'       => $result);
        
        $this->session->set("authCheck/$pennkey/$penn_id", $info);        
        return $result;
    }
    
}