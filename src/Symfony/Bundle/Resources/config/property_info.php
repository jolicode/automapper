<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

return static function (ContainerConfigurator $container) {
    $container
        ->services()
            ->set('automapper.property_info.reflection_source_extractor', ReflectionExtractor::class)
                ->args([
                    '$accessFlags' => ReflectionExtractor::ALLOW_PUBLIC,
                    '$mutatorPrefixes' => [],
                    '$arrayMutatorPrefixes' => [],
                    '$enableConstructorExtraction' => false,
                ])
            ->set('automapper.property_info.reflection_target_extractor', ReflectionExtractor::class)
                ->args([
                    '$accessFlags' => ReflectionExtractor::ALLOW_PUBLIC,
                    '$accessorPrefixes' => [],
                ])
            ->set('automapper.property_info.phpstan_source_extractor', PhpStanExtractor::class)
                ->args([
                    '$allowPrivateAccess' => false,
                    '$mutatorPrefixes' => [],
                    '$arrayMutatorPrefixes' => [],
                ])
            ->set('automapper.property_info.phpstan_target_extractor', PhpStanExtractor::class)
                ->args([
                    '$allowPrivateAccess' => false,
                    '$accessorPrefixes' => [],
                ])
            ->set('automapper.property_info.source_property_info_extractor', PropertyInfoExtractor::class)
                ->args([
                    '$typeExtractors' => [service('automapper.property_info.phpstan_source_extractor'), service('automapper.property_info.reflection_source_extractor')],
                ])
            ->set('automapper.property_info.target_property_info_extractor', PropertyInfoExtractor::class)
                ->args([
                    '$typeExtractors' => [service('automapper.property_info.phpstan_target_extractor'), service('automapper.property_info.reflection_target_extractor')],
                ])
            ->set('automapper.property_info.reflection_extractor', ReflectionExtractor::class)
                ->args([
                    '$accessFlags' => ReflectionExtractor::ALLOW_PUBLIC,
                ])
    ;

    if (interface_exists(AdapterInterface::class)) {
        $container->services()
            ->set('automapper.property_info.cache_pool_extractor', ArrayAdapter::class)
            ->set('automapper.property_info.cached_source_property_info_extractor', PropertyInfoCacheExtractor::class)
                ->args([
                    service('automapper.property_info.source_property_info_extractor'),
                    service('automapper.property_info.cache_pool_extractor'),
                ])
            ->set('automapper.property_info.cached_target_property_info_extractor', PropertyInfoCacheExtractor::class)
                ->args([
                    service('automapper.property_info.target_property_info_extractor'),
                    service('automapper.property_info.cache_pool_extractor'),
                ])
            ->alias('automapper.property_info.source_type_extractor', 'automapper.property_info.cached_source_property_info_extractor')
            ->alias('automapper.property_info.target_type_extractor', 'automapper.property_info.cached_target_property_info_extractor')
        ;
    } else {
        $container->services()
            ->alias('automapper.property_info.source_type_extractor', 'automapper.property_info.source_property_info_extractor')
            ->alias('automapper.property_info.target_type_extractor', 'automapper.property_info.target_property_info_extractor')
        ;
    }
};
