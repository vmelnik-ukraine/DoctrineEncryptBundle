<?php

namespace VMelnik\DoctrineEncryptBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use VMelnik\DoctrineEncryptBundle\DependencyInjection\VMelnikDoctrineEncryptExtension;
use VMelnik\DoctrineEncryptBundle\DependencyInjection\Compiler\RegisterServiceCompilerPass;


class VMelnikDoctrineEncryptBundle extends Bundle {
    
    public function build(ContainerBuilder $container) {
        parent::build($container);
        $container->addCompilerPass(new RegisterServiceCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
    
    public function getContainerExtension()
    {
        return new VMelnikDoctrineEncryptExtension();
    }
}
