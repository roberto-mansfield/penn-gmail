<?php

namespace Penn\CosignSSOBundle\Security\Authentication\Provider;

Class Cosign {

	private $web_app;
	private $pennkey;
	private $ip_address;
	private $service_name;
	private $token;
	
	function __construct($web_app) {
		$this->web_app      = $web_app;
		$this->ip_address   = ( isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1' );	
		$this->pennkey      = null;
		$this->service_name = null;

		// validate to grab pennkey (and ensure it was set via cosign rather than some other auth)
		$this->validate();
	}

	private function validate() {

		// code from ISC prowiki
		if ( isset($_SERVER['REMOTE_USER']) && !empty($_SERVER['REMOTE_USER']) ) {

			// REMOTE_USER received, however auth could have been
			// via HTTP Basic or Digest; we check further below
			if ( $_SERVER['AUTH_TYPE'] == 'Cosign' && isset($_SERVER['COSIGN_SERVICE']) ) {

				 // PHP replaces '.' with '_' in $_COOKIE array keys, so we
				 // do the same in order to index the CoSign service cookie
				$this->service_name = self::getServiceName();
		
				if ( isset( $_COOKIE[$this->service_name] ) ) {
					$this->pennkey = $_SERVER['REMOTE_USER'];
					$this->token   = $_COOKIE[$this->service_name];
				}
			}
		}
	}

	public function pennkey() {
		return $this->pennkey;
	}
	
	/**
	 * Expire our cosign authentication cookie.
	 */
	public static function expire() {
	
	    $service_name = self::getServiceName();
	    
	    // expire cosign cookie
	    if ( isset($_COOKIE[$service_name]) ) {
	        setcookie($service_name, 'expire', time()-86400, '/', null, true);
	    }
	}


	/**
	 * Return the logout URL for a global logout of all web sessions.
	 * @return string
	 */
	public static function globalLogoutUrl() {
	    return "https://weblogin.pennkey.upenn.edu/logout";
	}
	
	
	/**
	 * Return the cosign service name.
	 * @string
	 */
	public static function getServiceName() { 
	    return str_replace('.', '_', $_SERVER['COSIGN_SERVICE']);
	}
	
	
}
