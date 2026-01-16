<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MapperServiceLocatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('automapper.mapper_service_locator')) {
            return;
        }

        $definition = $container->getDefinition('automapper.mapper_service_locator');
        $mapperServiceLocatorServices = $container->findTaggedServiceIds('automapper.mapper_service');

        $map = [];
        foreach ($mapperServiceLocatorServices as $id => $tags) {
            $map[$id] = new ServiceClosureArgument(new Reference($id));
        }

        $definition->setArgument(0, $map);
    }
}
