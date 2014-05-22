This bundle provides an interface to the PennGroups server for queries
via LDAP. 

To use this bundle, make sure you've added the local IRAD bundle repo to
your composer.json file.

(See: http://titania.sas.upenn.edu/repo/symfony/index.html)

Add the deployment bundle to your "require" section:

    "require": {
        "sas-irad/penngroups-query-bundle": "[your desired version]"
    },

You can override these default parameters in your config files:

    penn_groups:

        ## the PennGroups username
        username:  "irad-authz/sas.upenn.edu"

        ## the file with the encrypted credential
        credential:  "%kernel.root%/config/penngroups.txt"

        ## the private key file for decrypting the credential
        key:  "%kernel.root%/config/keys/private.pem"
        
        
If you are having trouble with LDAP connections to penngroups, it could be
a certificate issue. You can disable certificate checks (in development 
environments) by updating your /etc/ldap/ldap.conf file with the setting:

  TLS_REQCERT  never
