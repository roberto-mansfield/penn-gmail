<?php

namespace SAS\IRAD\PersonInfoBundle\Service;

use SoapClient;
use SimpleXMLElement;

// use ini_set to adjust wsdl cache time-to-live value during development
// ini_set("soap.wsdl_cache_ttl", 1);

Class PersonInfoClient {
    
    private $params;
    private $client;
    private $soap;
    
    public function __construct($params) {
        
        $this->params = $params;
        
        // options for soap client
        $options = array('cache_wsdl' => WSDL_CACHE_NONE,
                         'trace'      => true);
        
        $this->client = $this->params['client'];
        $this->soap = new SoapClient($this->params['wsdl'], $options);
    }

    /**
     * Return information from Penn Community using someone's pennkey. The fields returned
     * are specified on the SOAP server's config file for this client.
     * @param string $pennkey Pennkey to query.
     * @return PersonInfo
     */
    public function searchByPennkey($pennkey) {
        $info = $this->getInfo('PersonInfoFromPennkey', compact('pennkey'));
        $info['pennkey'] = $pennkey;
        return new PersonInfo($info);
    }

    /**
     * Return information from Penn Community using someone's penn_id. The fields returned
     * are specified on the SOAP server's config file for this client.
     * @param string $pennkey Pennkey to query.
     * @return PersonInfo
     */
    public function searchByPennID($penn_id) {
        $info = $this->getInfo('PersonInfoFromPennID', compact('penn_id'));
        $info['penn_id'] = $penn_id;
        return new PersonInfo($info);
    }

    /**
     * Function getInfo builds our signed and encrypted SOAP request, issues the request,
     * decrypts and verifies the results. Returns a SimpleXML object as a result.
     * @param string $command Command we will send to our service (after decryption).
     * @param array $parameters Parameters to pass to $command.
     * @return XMLObject
     */
    public function getInfo($command, $parameters) {

        // create our message which will be encrypted

        $message   = new SimpleXmlElement("<message></message>");
        $client    = $message->addChild("client", $this->client);
        $content   = $message->addChild("content");

        $content->addChild($command);
        $content->$command->addChild('parameters');
        
        foreach ( $parameters as $parameter => $value ) {
            $content->$command->parameters->addChild($parameter, $value);
        }

        // read in our service, public and private keys
        $service_key = $this->getKey($this->params['service_key'], 'public');
        $public_key  = $this->getKey($this->params['public_key'],  'public');
        $private_key = $this->getKey($this->params['private_key'], 'private');

        // encrypt our content message. server will validate that sig belongs to client 
        // (php 5.1 and 5.2 differ in the asXML output. remove newlines so that sig is the
        // same between versions)
        $snippet = preg_replace("/\s/", "", $message->content->children()->asXML());
        openssl_sign($snippet, $signature, array('0' => $private_key, '1' => null));

        // add signature to message
        $signature = $message->addChild("signature", base64_encode($signature));

        // now encrypt request in ssl envelope using the service's public key
        $encrypted    = null;
        $envelope_key = null;
        openssl_seal($message->asXML(), $encrypted, $envelope_key, array($service_key));

        // Send request to web service
        try {
            $result = $this->soap->getInfo(base64_encode($encrypted), base64_encode($envelope_key[0]));
        } 
        catch (Exception $error) {
            $this->log("SOAP error generated when calling getInfo(): " . $error->getMessage()); 
            return;
        }

        // Decrypt, validate and parse response from web service
        // parse out the response values from the xml
        try {
            $response = new SimpleXMLElement($result);
        }
        catch (\Exception $error) {
            $this->log("Response from SOAP server cannot be parsed as XML: " . $this->soap->__getLastResponse());
            return;
        }

        $message  = base64_decode( $response->message  );
        $envelope = base64_decode( $response->envelope );
        
        // now decrypt response message using clients private key
        $decrypted = null;

        if ( !openssl_open($message, $decrypted, $envelope, $private_key) ) {
            $this->error_log("Error decrypting response from service");
            exit;
        }

        $message = new SimpleXMLElement($decrypted);

        // check signature
        $content   = $message->content;
        $signature = (string) $message->signature;

        if ( openssl_verify($content, base64_decode($signature), $service_key) != 1 ) {
            $this->log("Invalid signature in message");
            exit;
        }

        // decode content
        $content = base64_decode($content);

        // release our key resources
        openssl_free_key($service_key);
        openssl_free_key($public_key);
        openssl_free_key($private_key);

        try {
            $info = new SimpleXMLElement($content);
        }
        catch (Exception $error) {
            $this->log("Content from SOAP result cannot be parsed as XML");
            return;
        }

        // convert $info to array
        $result = array();
        foreach ( $info->children() as $element) {
            $result[strtolower($element->getName())] = (string) $element;
        }
        
        // convert names to name case
        $convert = new NameCase($result);
        $result['first_name'] = $convert->firstName();
        $result['last_name']  = $convert->lastName();
        
        return $result;
    }



    /**
     * Read in public/private key from a file. We need to specify if the key is public or private so
     * we can use the correct openssl command.
     * @param string $key_file Path to the file with the key information.
     * @param string $key_type Either 'public' or 'private' depending on the key type.
     * @return resource Returns an openssl key resource.
     */
    private function getKey($key_file, $key_type) {
    
        global $CONFIG; 

        if ( !in_array($key_type, array('private', 'public')) ) {
            $this->log("Invalid value for key_type; $key_type");
            exit;
        }

        // retrieve key data from file
        $key_data = file_get_contents($key_file);
        
        // read key into resource
        if ( $key_type == 'private' ) {
            $key = openssl_pkey_get_private($key_data);
        } else {
            $key = openssl_pkey_get_public($key_data);
        }
            
        // check for any problem reading in key
        if ( !is_resource($key) ) {
            $this->log("Error retrieving $key_type key for '{$this->client}'");
            exit;
        }
        
        return $key;
    } 

    /**
     * Logs error to the web server error log.
     * @param string $message Message to record in the error log.
     */
    private function log($message) {
        error_log("[PersonInfoClient] $message");
    }
    

}