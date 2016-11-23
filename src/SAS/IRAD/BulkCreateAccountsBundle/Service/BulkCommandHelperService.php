<?php

namespace SAS\IRAD\BulkCreateAccountsBundle\Service;

use SAS\IRAD\PennGroupsBundle\Service\WebServiceQuery;

class BulkCommandHelperService {
    
    /**
     * Parameters from config
     * @var array
     */
    private $params;
    
    /**
     * Timezone parameter from account logger settings
     * @var DateTimeZone
     */
    private $timezone;
    
    
    /**
     * File handle for error log file
     * @var resource
     */
    private $fh;
    
    
    public function __construct(array $bulk_params) {

        $this->params = $bulk_params;

        $this->timezone = new \DateTimeZone($bulk_params['log_timezone']);
        
        $log_dir    = $this->params['log_dir'];
        $log_prefix = $this->params['log_prefix'];
        $log_file   = "$log_dir/$log_prefix-" . date('Y-m-d') . ".log";
    
        $this->fh = fopen($log_file, "a");
    }

    public function __destruct() {
        if ( $this->fh ) {
            fclose($this->fh);
        }
    }
    
    /**
     * Generate a random password for our new account
     * @return string
     */
    public function randomPassword() {
        $password = '';
        foreach ( range(1,30) as $i ) {
            $password .= chr(rand(33, 126));
        }
        return $password;
    }    
    
    public function errorLog($text) {
        $timestamp = new \DateTime("now", $this->timezone);
        fwrite($this->fh, $timestamp->format('Y/m/d H:i:s ') . $text . "\n");
    }    
}