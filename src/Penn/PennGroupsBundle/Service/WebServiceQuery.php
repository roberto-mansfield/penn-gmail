<?php

namespace Penn\PennGroupsBundle\Service;

use Penn\PennGroupsBundle\Utility\Unlock;


class WebServiceQuery {
    
    private $username;
    private $credential;
    private $key;
    
    /**
     * Array of query string parameters to include in web api call
     * @var array
     */
    private $parameters;

    /**
     * the base url of the webservice to be called
     *
     * @access protected
     * @var string
     */
    private $service_url = 'https://medley.isc-seo.upenn.edu/grouperWs/servicesRest/v1_4_000';
    
    /**
     * the additional path to add to the webservice url
     * @var string
     */
    private $service_path;

    /**
     * The result code expected back from the webservice request
     * @var string
     */
    private $expected_result_code = 'SUCCESS';


    public function __construct($params) {
        // TODO: add checks for appropriate params
        $this->username   = $params['username'];
        $this->credential = $params['credential'];
        $this->key        = $params['key'];
        
        $this->parameters = array();
    }
    
    /**
     * Perform a web service query matching on penn_id. Return an array
     * of attributes for match or false if no result.
     * @param string $penn_id
     * @return array
     */
    public function findByPennID($penn_id) {

        if ( !preg_match('/^\d{8}$/', $penn_id) ) {
            throw new \Exception("Invalid penn_id passed.");
        }

        $this->setServicePath('subjects');
        
        $this->setParam('wsLiteObjectType', 'WsRestGetSubjectsLiteRequest');
        $this->setParam('subjectId', $penn_id);
        
        $result = $this->execute();
        
        if ( $result ) {
            return $result[0];
        } else {
            return false;
        }
    }
    

    /**
     * Perform a webservice query matching on pennkey. Return an array
     * of attributes for match or false if no result.
     * @param string $pennkey
     * @return array
     */
    public function findByPennkey($pennkey) {
    
        if ( !preg_match('/^[A-Za-z][A-Za-z0-9]{1,16}$/', $pennkey) ) {
            throw new \Exception("Invalid pennkey passed.");
        }

        $this->setServicePath('subjects');
        
        $this->setParam('wsLiteObjectType', 'WsRestGetSubjectsLiteRequest');
        $this->setParam('subjectIdentifier', $pennkey);
        
        $result = $this->execute();
        
        if ( $result ) {
            return $result[0];
        } else {
            return false;
        }
    }
    
    /**
     * Set a parameter for our web api call
     * @param string $param_name
     * @param string $param_value
     */
    private function setParam($param_name, $param_value) {
        $this->parameters[$param_name] = $param_value;
    }

    /**
     * Set a parameter for our web api call
     * @param string $param_name
     * @param string $param_value
     */
    private function setServicePath($service_path) {
        $this->service_path = $service_path;
    }    
    
    
    /**
     * Return a query string version of our api parameters
     * @return string
     */
    private function getQueryString() {
        
        $units = array();
        foreach ( $this->parameters as $key => $value ) {
            $units[] = urlencode($key) . '=' . urlencode($value);
        }

        return implode('&', $units);
    }
    
    /**
     * execute the call to the webservice
     *
     * @access protected
     * @return SimpleXMLElement
     */
    protected function execute() {
    
        $results = array();

        // get credentials ready
        $unlock = new Unlock($this->key);
        $password = $unlock->file($this->credential);

        // we only want "human" results
        $this->setParam('sourceIds', 'pennperson');
        
        // set the attributes we want returned
        $this->setParam('subjectAttributeNames', 'PENNNAME,EMAIL,FIRST_NAME,LAST_NAME');
        
        // build url for rest api call
        $url = implode('/', array($this->service_url, $this->service_path)) . '?' . $this->getQueryString();
        
        $session = curl_init($url);
        
        curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-type: text/xml;charset="utf-8"'));
        curl_setopt($session, CURLOPT_HTTPGET, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        // necessary on groups.sas (old certs lists?)
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);
        
        // set authentication header
        curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($session, CURLOPT_USERPWD, $this->username . ":" . $password);
    
        //do the call
        $response = curl_exec($session);

        if ( $response === false ) {
            throw new \Exception('Connection Error:' . curl_error($session));
            
        } 

        // did we get back valid xml?
        try {
            $xml = simplexml_load_string($response);

        } catch (\Exception $e) {
            throw new \Exception("Webservice did not return valid xml: $response");    
        }
            
        // did we get a valid query result?
        if ( (string) $xml->resultMetadata->resultCode != $this->expected_result_code ) {
            // throw exception on error? (but not empty search results)
            throw new \Exception((string) $xml->resultMetadata->resultMessage);
        }
        
        // did we find anything?
        if ( !$xml->wsSubjects || (string) $xml->wsSubjects->WsSubject->resultCode != $this->expected_result_code ) {
            return false;
        }

        // okay, we should have valid query results at this point
        // we need to flip our attribute descriptions so we can refer to them by name
        $attribute = array();
        $index     = 0;
        foreach ( $xml->subjectAttributeNames->string as $attribName ) {
            $attribute[(string) $attribName] = $index++;
        }
        
        // convert xml search results to array (if we have any)
        foreach ( $xml->wsSubjects->WsSubject as $subject ) {

            $personAttributes = (array) $subject->attributeValues->string;
            
            $person = array('penn_id'     => (string) $subject->id,
                            'name'        => (string) $subject->name,
                            'first_name'  => (string) $subject->attributeValues->string[$attribute['FIRST_NAME']],
                            'last_name'   => (string) $subject->attributeValues->string[$attribute['LAST_NAME']],
                            'pennkey'     => (string) $subject->attributeValues->string[$attribute['PENNNAME']],
                            'email'       => (string) $subject->attributeValues->string[$attribute['EMAIL']]);
            
            // if "name" is empty, build it from first/last
            if ( !$person['name'] ) {
                $person['name'] = $person['first_name'] . ' ' . $person['last_name'];
            }
            
            $results[] = $person;
        }
        
        curl_close($session);

        // clear our values after a query
        $this->parameters = array();
        $this->setServicePath(null);
        
        return $results;
    }

   
}