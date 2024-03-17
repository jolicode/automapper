<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle;

use AutoMapper\Symfony\Bundle\DependencyInjection\AutoMapperExtension;
use AutoMapper\Symfony\Bundle\DependencyInjection\Compiler\PropertyInfoPass;
use AutoMapper\Symfony\Bundle\DependencyInjection\Compiler\TransformerFactoryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AutoMapperBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PropertyInfoPass());
        $container->addCompilerPass(new TransformerFactoryPass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new AutoMapperExtension();
    }
}
