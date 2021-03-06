<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new SAS\IRAD\CosignSSOBundle\CosignSSOBundle(),
            new SAS\IRAD\FileStorageBundle\FileStorageBundle(),
            new SAS\IRAD\GmailAccountLogBundle\GmailAccountLogBundle(),
            new SAS\IRAD\GoogleOAuth2TokenBundle\GoogleOAuth2TokenBundle(),
            new SAS\IRAD\GoogleAdminClientBundle\GoogleAdminClientBundle(),
            new SAS\IRAD\PennGroupsBundle\PennGroupsBundle(),
                
            new Penn\AssetsBundle\AssetsBundle(),
            new SAS\IRAD\GMailConfigureBundle\GMailConfigureBundle(),
            new SAS\IRAD\MailForwardingBundle\MailForwardingBundle(),
            new SAS\IRAD\BulkCreateAccountsBundle\BulkCreateAccountsBundle(),
            new SAS\IRAD\UserInfoBundle\UserInfoBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
