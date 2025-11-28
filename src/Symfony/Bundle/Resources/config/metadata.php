<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Configuration;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Extractor\SourceTargetMappingExtractor;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Transformer\TransformerFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(SourceTargetMappingExtractor::class)
            ->args([
                service(Configuration::class),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.source_type_extractor'),
                service('automapper.property_info.target_type_extractor'),
            ])

        ->set(FromSourceMappingExtractor::class)
            ->args([
                service(Configuration::class),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.source_type_extractor'),
                service('automapper.property_info.target_type_extractor'),
            ])

        ->set(FromTargetMappingExtractor::class)
            ->args([
                service(Configuration::class),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.reflection_extractor'),
                service('automapper.property_info.source_type_extractor'),
                service('automapper.property_info.target_type_extractor'),
            ])

        ->set(MetadataFactory::class)
            ->args([
                service(Configuration::class),
                service(SourceTargetMappingExtractor::class),
                service(FromSourceMappingExtractor::class),
                service(FromTargetMappingExtractor::class),
                service(TransformerFactoryInterface::class),
                service(EventDispatcherInterface::class),
                service('automapper.config_mapping_registry'),
                service(ClassDiscriminatorResolver::class),
            ])
    ;
};
