<?php

namespace Penn\PennGroupsBundle\Service;

use Penn\PennGroupsBundle\Utility\Unlock;

class LDAPQuery {
    
    private $username;
    private $credential;
    private $key;
    private $filter;
    
    public function __construct($username, $credential, $key) {
        // TODO: add checks for appropriate params
        $this->username   = $params['username'];
        $this->credential = $params['credential'];
        $this->key        = $params['key'];
        
        $this->filter     = array();
    }
    
    
    /**
     * Perform an ldap query matching on penn_id. Return an array
     * of attributes for match or false if no result.
     * @param string $penn_id
     * @return array
     */
    public function findByPennID($penn_id) {

        if ( !preg_match('/^\d{8}$/', $penn_id) ) {
            throw new \Exception("Invalid penn_id passed.");
        }
        
        $this->setFilter('pennid', $penn_id);
        $result = $this->execute();
        
        return $result;
    }
    

    /**
     * Perform an ldap query matching on pennkey. Return an array
     * of attributes for match or false if no result.
     * @param string $pennkey
     * @return array
     */
    public function findByPennkey($pennkey) {
    
        if ( !preg_match('/^[A-Za-z][A-Za-z0-9]{1,16}$/', $pennkey) ) {
            throw new \Exception("Invalid pennkey passed.");
        }
        
        $this->setFilter('pennname', $pennkey);
        $result = $this->execute();
        
        return $result;
    }
    
    
    private function execute() {
        
        $this->user = "penngroups.ldap_query";
        
        // settings for ldap connection and query
        $pg_server = 'penngroups.upenn.edu';
        $bind_dn   = "uid={$this->username},ou=entities,dc=upenn,dc=edu";
        $base_dn   = 'ou=pennnames,dc=upenn,dc=edu';
        $attrs     = array('pennname', 'pennid');

        // construct our search filter
        //$filter    = "(&(pennname= " . $pennkey . ")(objectClass=*))";
        $filter    = $this->getQueryFilter();
        
        // uncomment for detailed debugging
        // ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        $lh = ldap_connect($pg_server);
    
        if ( !$lh ) {
            throw new \Exception("ldap_connect failed: " . ldap_error($lh));
        }
    
        ldap_set_option($lh, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($lh, LDAP_OPT_REFERRALS, 0);
    
        if ( !ldap_start_tls($lh) ) {
            throw new \Exception("ldap_start_tls failed: " . ldap_error($lh));
        }
    
        // get credentials ready
        $unlock = new Unlock($this->key);
        //$password = $unlock->file($this->credential);
        $password = "9gvZnIM3pEVqhkwkHneaMAQD8";

        if ( !ldap_bind($lh, $bind_dn, $password) ) {
            throw new \Exception("ldap_bind failed: "  . ldap_error($lh));
        }
    
        $results = ldap_search($lh, $base_dn, $filter, $attrs);
        
        if ( !$results ) {
            // search failed with error
            throw new \Exception("ldap_search failed: " . ldap_error($lh));
        }

        // extract entries from $results resource
        $entries = ldap_get_entries($lh, $results);
        if ( $entries === false ) {
            throw new \Exception("ldap_get_entries failed: " . ldap_error($lh));
        }
        
        if ( $entries['count'] === 0 ) {
            // valid query, but nothing found
            return false;
        }

        if ( $entries['count'] > 1 ) {
            // we didn't get a unique result
            throw new \Exception("Error: ldap returned multiple entries when only one expected.");
        }
    
        // close ldap
        ldap_unbind($lh);
    
        // so we should have a single unique result at this point
        $result = array('penn_id' => $entries[0]['pennid'][0],
                        'pennkey' => $entries[0]['pennname'][0] );

        // clear our query filter
        $this->filter = array();

        return $result;
    }


    /**
     * Sets a filter value in our filter array. Appropriate keys are pennname and pennid.
     * @param string $key
     * @param string $value
     */
    private function setFilter($key, $value) {
        $this->filter[$key] = $value;
    }
    
    
    /**
     * Construct the LDAP filter string for either penn_id or pennkey search
     */
    private function getQueryFilter() {
        
        $units = array();
        foreach ( $this->filter as $key => $value) {
            $units[] = "$key=$value";
        }
        
        $filter = implode(',', $units);
        
        return "(&($filter)(objectClass=*))";
    }
    
}