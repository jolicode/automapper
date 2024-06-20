<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Extractor\ReadWriteTypeExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

return static function (ContainerConfigurator $container) {
    $container
        ->services()
            ->set('automapper.property_info.reflection_extractor', ReflectionExtractor::class)
                ->args([
                    '$mutatorPrefixes' => null,
                    '$accessorPrefixes' => null,
                    '$arrayMutatorPrefixes' => null,
                    '$enableConstructorExtraction' => true,
                    '$accessFlags' => ReflectionExtractor::ALLOW_PUBLIC,
                ])
            ->set('automapper.property_info.read_write_type_extractor', ReadWriteTypeExtractor::class)
            ->set('automapper.property_info.phpstan_extractor', PhpStanExtractor::class)
            ->set('automapper.property_info', PropertyInfoExtractor::class)
                ->args([
                    [service('automapper.property_info.reflection_extractor')],
                    [service('automapper.property_info.read_write_type_extractor'), service('automapper.property_info.phpstan_extractor'), service('automapper.property_info.reflection_extractor')],
                    [service('automapper.property_info.reflection_extractor')],
                    [service('automapper.property_info.reflection_extractor')],
                    [service('automapper.property_info.reflection_extractor')],
                ])
    ;
};
