<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\DependencyInjection\Compiler;

use AutoMapper\Extractor\ReadWriteTypeExtractor;
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

        $container->setDefinition(
            'automapper.property_info.reflection_extractor',
            new Definition(
                ReflectionExtractor::class,
                [
                    '$mutatorPrefixes' => null,
                    '$accessorPrefixes' => null,
                    '$arrayMutatorPrefixes' => null,
                    '$enableConstructorExtraction' => true,
                    '$accessFlags' => ReflectionExtractor::ALLOW_PUBLIC | ReflectionExtractor::ALLOW_PROTECTED | ReflectionExtractor::ALLOW_PRIVATE,
                ]
            )
        );

        $container->setDefinition(
            'automapper.property_info.read_write_type_extractor',
            new Definition(
                ReadWriteTypeExtractor::class,
                []
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
                        new Reference('automapper.property_info.read_write_type_extractor'),
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
