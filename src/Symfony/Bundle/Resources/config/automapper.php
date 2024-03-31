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
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Provider\ProviderRegistry;
use AutoMapper\Symfony\ExpressionLanguageProvider;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(AutoMapper::class)
            ->args([
                service(ClassLoaderInterface::class),
                service(PropertyTransformerRegistry::class),
                service('automapper.config_mapping_registry'),
                service(ProviderRegistry::class),
                service(ExpressionLanguageProvider::class),
            ])
            ->alias(AutoMapperInterface::class, AutoMapper::class)->public()
            ->alias(AutoMapperRegistryInterface::class, AutoMapper::class)->public()

        ->set(EvalLoader::class)
            ->args([
                service(MapperGenerator::class),
                service(MetadataFactory::class),
            ])

        ->set(FileLoader::class)
            ->args([
                service(MapperGenerator::class),
                service(MetadataFactory::class),
                '%kernel.cache_dir%/automapper',
                true,
            ])

        ->set(Configuration::class)
    ;
};
