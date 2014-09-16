<?php

namespace SAS\IRAD\BulkCreateAccountsBundle\Service;


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
    
    
    public function __construct(array $params, array $account_logger_params) {

        $this->params = $params;
        $this->account_logger_params = $account_logger_params;
        $this->timezone = new \DateTimeZone($account_logger_params['timezone']);
        
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
    
    public function getEligibleStudents() {
        
        $students = $this->loadData($this->params['student_list']);
        $facstaff = $this->loadData($this->params['facstaff_list']);
        
        // remove "fake" penn_ids: 0999999
        foreach ( $students as $index => $penn_id ) {
            if ( preg_match("/^0\d{7}$/", $penn_id) ) {
                unset($students[$index]);
            }
        }
        
        // remove any facstaff entries from students
        foreach ( array_intersect($students, $facstaff) as $penn_id ) {
            $index = array_search($penn_id, $students);
            unset($students[$index]);
        }
        
        return $students;
    }

    public function loadData($file) {
    
        $records = array();
    
        foreach ( explode("\n", file_get_contents($file)) as $line ) {
            if ( $line ) {
                // assume penn_id is first column
                $data = str_getcsv($line);
                $records[] = $data[0];
            }
        }
        return $records;
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