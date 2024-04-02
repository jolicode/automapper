<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Configuration;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Metadata\MetadataRegistry;
use AutoMapper\Symfony\Bundle\CacheWarmup\CacheWarmer;
use AutoMapper\Symfony\Bundle\Command\DebugMapperCommand;
use AutoMapper\Symfony\Bundle\DataCollector\MetadataCollector;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(CacheWarmer::class)
            ->args([
                service('automapper.config_mapping_registry'),
                service(MetadataFactory::class),
                service(ClassLoaderInterface::class),
                '%automapper.cache_dir%',
            ])
            ->tag('kernel.cache_warmer')

        ->set('automapper.config_mapping_registry')
            ->class(MetadataRegistry::class)
            ->args([
                service(Configuration::class),
            ])

        ->set('automapper.data_collector.metadata')
            ->class(MetadataCollector::class)
            ->args([
                service(MetadataFactory::class),
                service('automapper.config_mapping_registry'),
            ])
            ->tag('data_collector')

        ->set('automapper.command.debug_mapper')
            ->class(DebugMapperCommand::class)
            ->args([
                service(MetadataFactory::class),
            ])
            ->autoconfigure()
    ;
};
