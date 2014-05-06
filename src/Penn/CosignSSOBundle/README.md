Setup (Symfony 2.1):

1. Add/register CosignSSOBundle in app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Penn\CosignSSOBundle\CosignSSOBundle(),


2. Setup a firewall rule with cosign_sso for your protected content:

    firewalls:
        protected:
            pattern:    ^/.*
            cosign_sso:
              cosign_app: YourCosignAppName  
              
The cosign_app parameter is user defined. You may use any name you want for your app.

              
43 In your code, use the "cosign_util" service in your controller to perform cosign functions:

    $cosign = $this->get('cosign_util');
    
    // expire a cosign session
    $cosign->expire();
    
    // redirect to global logout
    return $this->redirect($cosign->globalLogoutUrl());
