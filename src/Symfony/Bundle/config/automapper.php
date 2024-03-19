<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\Configuration;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Loader\EvalLoader;
use AutoMapper\Loader\FileLoader;
use AutoMapper\Metadata\MetadataRegistry;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(AutoMapper::class)
            ->args([
                service(ClassLoaderInterface::class),
                service(PropertyTransformerRegistry::class),
                service(MetadataRegistry::class),
            ])
            ->alias(AutoMapperInterface::class, AutoMapper::class)->public()
            ->alias(AutoMapperRegistryInterface::class, AutoMapper::class)->public()

        ->set(EvalLoader::class)
            ->args([
                service(MapperGenerator::class),
                service(MetadataRegistry::class),
            ])

        ->set(FileLoader::class)
            ->args([
                service(MapperGenerator::class),
                service(MetadataRegistry::class),
                '%kernel.cache_dir%/automapper',
                true,
            ])
            ->alias(ClassLoaderInterface::class, FileLoader::class)

        ->set(Configuration::class)
    ;
};
