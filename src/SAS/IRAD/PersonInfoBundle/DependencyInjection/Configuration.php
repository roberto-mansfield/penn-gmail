<?php

namespace SAS\IRAD\PersonInfoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('person_info');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()

                ->scalarNode('client')
                ->end()
    
                ->scalarNode('wsdl')
                ->defaultValue("https://fission.sas.upenn.edu/service/PersonInfo/index.php?wsdl")
                ->end()
                
                ->scalarNode('public_key')
                ->defaultValue("%kernel.root_dir%/config/keys/person_info/private.pem")
                ->end()
    
                ->scalarNode('private_key')
                ->defaultValue("%kernel.root_dir%/config/keys/person_info/private.pem")
                ->end()
                
                ->scalarNode('service_key')
                ->defaultValue("%kernel.root_dir%/config/keys/person_info/service.pem")
                ->end()
                
            ->end();
        
        return $treeBuilder;
    }
}
