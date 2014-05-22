<?php

namespace Penn\PennGroupsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PennGroupsExtension extends Extension {
    
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container) {
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        // inject our parameters into the container
        foreach ( $config as $key => $value ) {
            $container->setParameter("penngroups.$key", $value);
        }

        // load our services.yml parameters
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
