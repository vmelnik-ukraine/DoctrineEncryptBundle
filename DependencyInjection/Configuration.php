<?php

namespace TDM\DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration tree for security bundle. Full tree you can see in Resources/docs
 * 
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tdm_doctrine_encrypt');
        $supportedDrivers = array('orm', 'odm');
        $supportedEncryptors = array('aes256');

        // Grammar of config tree
        $rootNode
                ->children()
                    ->scalarNode('secret_key')
                        ->beforeNormalization()
                        ->ifNull()
                            ->thenInvalid('You must specifiy secret_key option')
                        ->end()
                    ->end()
                    ->scalarNode('encryptor')
                        ->validate()
                        ->ifNotInArray($supportedEncryptors)
                            ->thenInvalid('You must choose from one of provided encryptors or specify your own encryptor class through encryptor_class option')
                        ->end()
                        ->defaultValue($supportedEncryptors[0])
                    ->end()
                    ->scalarNode('encryptor_class')
                    ->end()
                    ->scalarNode('encryptor_service')
                    ->end()
                    ->scalarNode('db_driver')
                        ->validate()
                            ->ifNotInArray($supportedDrivers)
                                ->thenInvalid('The driver %s is not supported. Please choose one of ' . json_encode($supportedDrivers))
                            ->end()
                            ->cannotBeOverwritten()
                        ->defaultValue($supportedDrivers[0])
                        ->cannotBeEmpty()
                    ->end()
                ->end();

        return $treeBuilder;
    }

}