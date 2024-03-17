<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\Symfony\Bundle\CacheWarmup\CacheWarmer;
use AutoMapper\Symfony\Bundle\CacheWarmup\ConfigurationCacheWarmerLoader;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(CacheWarmer::class)
            ->args([
                service(AutoMapperRegistryInterface::class),
                tagged_iterator('automapper.cache_warmer_loader'),
                '%automapper.cache_dir%',
            ])
            ->tag('kernel.cache_warmer')

        ->set(ConfigurationCacheWarmerLoader::class)
            ->args([
                [], // mappers list from config
            ])
            ->tag('automapper.cache_warmer_loader')
    ;
};
