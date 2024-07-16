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
use AutoMapper\Loader\FileReloadStrategy;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Provider\ProviderRegistry;
use AutoMapper\Symfony\ExpressionLanguageProvider;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

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

        ->set('automapper.file_loader_lock_factory_store')
            ->class(FlockStore::class)
        ->set('automapper.file_loader_lock_factory')
            ->class(LockFactory::class)
            ->args([
                service('automapper.file_loader_lock_factory_store'),
            ])
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
                service('automapper.file_loader_lock_factory'),
                FileReloadStrategy::ALWAYS,
            ])

        ->set(Configuration::class)
    ;
};
