<?php

namespace SAS\IRAD\MailForwardingBundle\Service;

use SAS\IRAD\FileStorageBundle\Service\FileStorageService;
use SAS\IRAD\GmailAccountLogBundle\Service\AccountLogger;
use SAS\IRAD\PersonInfoBundle\PersonInfo\PersonInfoInterface;


class MailForwardingService {

    private $data;
    private $storage;
    private $logger;
    private $file;
    private $params;
    private $gParams;
    
    public function __construct(FileStorageService $storage, AccountLogger $logger, $params, $googleParams) {
        
        $this->data    = array();
        $this->storage = $storage;
        $this->logger  = $logger;
        $this->params  = $params;
        $this->gParams = $googleParams;
        
        $this->file    = $this->storage->init($params['data_file']);
    }
    
    /**
     * Return current forwarding data for a user
     * @param string $user
     * @return array
     */
    public function getForwarding($user) {
        
        $this->parse($this->file->get());
        return ( isset($this->data[$user]) ? $this->data[$user] : array() ); 
    }
    
    
    /**
     * Set the current forwarding data for a user. Requires file locking 
     * to avoid race conditions on updates.
     * @param PersonInfoInterface $personInfo
     * @param array $forward
     * @return boolean
     */
    public function setForwarding(PersonInfoInterface $personInfo, $forward) {
        
        $pennkey = $personInfo->getPennkey();
        
        if ( !$pennkey ) {
            throw new \Exception("Unable to set forwarding without pennkey in PersonInfo");
        }
        
        if ( !$forward ) {
            return false;
        }
        
        $user = $pennkey . '@' . $this->gParams['domain'];
        
        // read and parse forwarding data with write lock
        $this->parse($this->file->getAndHold());

        if ( !is_array($forward) ) {
            $forward = array($forward);
        }
        
        // remove any duplicates
        $forward = array_unique($forward);
        
        // is the data changing?
        $changed = false;
        if ( isset($this->data[$user]) ) {
            if ( array_diff($this->data[$user], $forward) || array_diff($forward, $this->data[$user]) ) {
                $changed = true;
            }
        } else {
            $changed = true;
        }
        
        // update data file if change detected
        if ( $changed ) {
        
            $this->data[$user] = $forward;
            
            try {
                $this->file->saveAndRelease($this->serialize());
                $this->logger->log($personInfo, 'UPDATE', "Mail forwarded to " . implode(", ", $forward));
            } catch (\Exception $e) {
                $this->logger->log($personInfo, 'ERROR', $e->getMessage());
                throw $e;
            }
        } else {
            // close and release lock on file
            $this->file->close();
        }
    }
    
    /**
     * Set the current forwarding for a user to the gmail relay.
     * @param PersonInfoInterface $personInfo
     */
    public function setGmailForwarding(PersonInfoInterface $personInfo) {
        
        $pennkey = $personInfo->getPennkey();
        
        if ( !$pennkey ) {
            throw new \Exception("Unable to set Gmail forwarding without pennkey in PersonInfo");
        }
        
        $forward = $pennkey . '@' . $this->gParams['relay_domain'];
        
        return $this->setForwarding($personInfo, $forward);
    }
    
    /**
     * Return the max_forwards parameter
     * @return integer
     */
    public function getMaxForwards() {
        return intval($this->params['max_forwards']);
    }
    
    /**
     * Return an array of domains equivalent to our current domain
     * @return array
     */
    public function getEquivalentDomains() {
        return $this->params['equivalent_domains'];
    }
    
    
    /**
     * Return the "type" of the forwarding: gmail, pennlive, other
     * @param array Array of forwards returned by getForwarding()
     * @return string
     */
    public function getForwardingType($forwards) {

        if ( count($forwards) == 0 ) {
            return 'none';
        }
        
        $forward = $forwards[0];
        list($user, $domain) = explode('@', $forward);
        
        if ( $domain == 'LIVE' ) {
            return 'pennlive';
        } elseif ( $domain == $this->gParams['relay_domain'] ) {
            return 'gmail';
        } else {
            return 'other';
        } 
    }
    
    
    /**
     * Test an email address to determine if it is suitable for mail forwarding
     * @param string
     * @return boolean
     */
    public function isAddressValid($email) {
        
        // test format of email address
        if ( !preg_match("/^(\w+)([\_\+\.\-]\w+)*\@(\w{2,}\.)+(\w{2,})$/", $email) ) {
            return false;
        }
        
        // next test if email is in an equivalent domain
        list($user, $domain) = explode('@', $email);
        
        if ( in_array($domain, $this->params['equivalent_domains']) ) {
            return false;
        }
        
        return true;
    }
            
    

    /**
     * Parse the forwarding data into an array structure. Data should be in the format:
     * 
     *    user@sas.upenn.edu{space}email1@domain.com{tab}email2@domain.com{tab}email3@domain.com{newline}
     *    
     * Sometimes a tab appears after the username so we use preg_split below to split on any 
     * whitespace. The file will be written back correctly though. The line may have 1-3 forwarding
     * email addresses.
     * @param string $contents
     * @return array
     */
    private function parse($contents) {
        
        $this->data = array();
        $lines = explode("\n", $contents);

        foreach ( $lines as $line ) {
            if ( $line ) {
                
                $result = preg_split("/\s+/", $line);
                
                // we should have more than one part
                if ( count($result) > 1 ) {
                    
                    $user    = $result[0];
                    $forward = array_slice($result, 1, count($result));
                    
                    $this->data[$user] = $forward;
                }
            }
        }
        return true;
    }
    
    /**
     * Serialize $this->data into the proper mail forwarding file format
     * @return string
     */
    private function serialize() {
    
        $content = false;
        $lines   = array();
        
        foreach ( $this->data as $user => $forwarding ) {
            array_push($lines, $user . ' ' . implode("\t", $forwarding));
        }
        return implode("\n", $lines);
    }    
}