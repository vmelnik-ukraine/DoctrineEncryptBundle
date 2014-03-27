<?php

namespace TDM\DoctrineEncryptBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use TDM\DoctrineEncryptBundle\DependencyInjection\TDMDoctrineEncryptExtension;
use TDM\DoctrineEncryptBundle\DependencyInjection\Compiler\RegisterServiceCompilerPass;


class TDMDoctrineEncryptBundle extends Bundle {
    
    public function build(ContainerBuilder $container) {
        parent::build($container);
        $container->addCompilerPass(new RegisterServiceCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
    
    public function getContainerExtension()
    {
        return new TDMDoctrineEncryptExtension();
    }
}
