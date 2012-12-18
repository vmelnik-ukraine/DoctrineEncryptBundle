<?php

namespace VMelnik\DoctrineEncryptBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use VMelnik\DoctrineEncryptBundle\DependencyInjection\VMelnikDoctrineEncryptExtension;


class VMelnikDoctrineEncryptBundle extends Bundle {
    public function getContainerExtension()
    {
        return new VMelnikDoctrineEncryptExtension();
    }
}
