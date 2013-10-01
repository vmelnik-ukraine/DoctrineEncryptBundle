<?php

namespace VMelnik\DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use VMelnik\DoctrineEncryptBundle\Encryptors\EncryptorServiceInterface;

/**
 * Initialization of bundle.
 *
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class VMelnikDoctrineEncryptExtension extends Extension {

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $services = array('orm' => 'orm-services');
        $supportedEncryptorClasses = array('aes256' => 'VMelnik\DoctrineEncryptBundle\Encryptors\AES256Encryptor');

        if (empty($config['secret_key'])) {
            if ($container->hasParameter('secret')) {
                $config['secret_key'] = $container->getParameter('secret');
            } else {
                throw new \RuntimeException('You must provide "secret_key" for DoctrineEncryptBundle or "secret" for framework');
            }
        }

        if (!empty($config['encryptor_class'])) {
            $encryptorFullName = $config['encryptor_class'];
        } else {
            $encryptorFullName = $supportedEncryptorClasses[$config['encryptor']];
        }

        $encryptorService = NULL;
        if (!empty($config['encryptor_service'])) {
            if (!$container->has($config['encryptor_service']))
                throw new \RuntimeException('Encryptor service must be a defined service.');
            $service = $container->get($config['encryptor_service']);
            if (!$service instanceof EncryptorServiceInterface)
                throw new \RuntimeException('Encryptor service must be an instance of "EncryptorServiceInterface".');
            $encryptorFullName = '';
            $encryptorService = $service;
        }
        $container->setParameter('vmelnik_doctrine_encrypt.encryptor_class_name', $encryptorFullName);
        $container->setParameter('vmelnik_doctrine_encrypt.secret_key', $config['secret_key']);
        $container->setParameter('vmelnik_doctrine_encrypt.encrypter_service', $encryptorService);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load(sprintf('%s.xml', $services[$config['db_driver']]));
    }

    public function getAlias() {
        return 'vmelnik_doctrine_encrypt';
    }

}
