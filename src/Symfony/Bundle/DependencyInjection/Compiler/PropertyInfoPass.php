<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class PropertyInfoPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('property_info')) {
            return;
        }

        $flags = ReflectionExtractor::ALLOW_PUBLIC;

        if ($container->getParameter('automapper.map_private_properties')) {
            $flags |= ReflectionExtractor::ALLOW_PRIVATE | ReflectionExtractor::ALLOW_PROTECTED;
        }

        $container->setDefinition(
            'automapper.property_info.reflection_extractor',
            new Definition(
                ReflectionExtractor::class,
                [
                    '$mutatorPrefixes' => null,
                    '$accessorPrefixes' => null,
                    '$arrayMutatorPrefixes' => null,
                    '$enableConstructorExtraction' => true,
                    '$accessFlags' => $flags,
                ]
            )
        );

        $container->setDefinition(
            'automapper.property_info',
            new Definition(
                PropertyInfoExtractor::class,
                [
                    new IteratorArgument([
                        new Reference('automapper.property_info.reflection_extractor'),
                    ]),
                    new IteratorArgument([
                        new Reference('property_info.phpstan_extractor'),
                        new Reference('automapper.property_info.reflection_extractor'),
                    ]),
                    new IteratorArgument([
                        new Reference('automapper.property_info.reflection_extractor'),
                    ]),
                    new IteratorArgument([
                        new Reference('automapper.property_info.reflection_extractor'),
                    ]),
                    new IteratorArgument([
                        new Reference('automapper.property_info.reflection_extractor'),
                    ]),
                ]
            )
        );

        $container->setDefinition(
            'automapper.property_info.cache',
            new Definition(PropertyInfoCacheExtractor::class, [
                new Reference('.inner'),
                new Reference('cache.property_info'),
            ])
        )->setDecoratedService('automapper.property_info');
    }
}
