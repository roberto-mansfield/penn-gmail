<?php

namespace Penn\PennGroupsBundle\Utility;

/*
 * Unlock an encrypted file using private key
 */

Class Unlock {
	
	private $private_key;
	
	function __construct($private_key_file) {
		$private_key_data = file_get_contents($private_key_file);
		$this->private_key = openssl_pkey_get_private($private_key_data);
	}

	function file($filename) {
		$decrypted = null;
		$data = base64_decode(file_get_contents($filename));
		openssl_private_decrypt($data, $decrypted, $this->private_key);
		return $decrypted;
	}	
}
