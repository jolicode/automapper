<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Configuration;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Metadata\MetadataRegistry;
use AutoMapper\Symfony\Bundle\CacheWarmup\CacheWarmer;

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
    ;
};
