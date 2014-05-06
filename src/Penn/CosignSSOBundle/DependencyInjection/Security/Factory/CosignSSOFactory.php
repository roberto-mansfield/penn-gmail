<?php

namespace Penn\CosignSSOBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class CosignSSOFactory implements SecurityFactoryInterface {
    
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint) {

        $providerId = 'security.authentication.provider.cosign_sso.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('cosign_sso.security.authentication.provider'))
            ->replaceArgument(1, new Reference($userProvider))
            ->replaceArgument(2, $config['cosign_app']);
        
        $listenerId = 'security.authentication.listener.cosign_sso.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('cosign_sso.security.authentication.listener'));

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition() {
        return 'pre_auth';
    }

    public function getKey() {
        return 'cosign_sso';
    }

    public function addConfiguration(NodeDefinition $node) {
        // defined a required cosign_app parameter for our cosign provider
        $node->children()->scalarNode('cosign_app')->end();
    }
}