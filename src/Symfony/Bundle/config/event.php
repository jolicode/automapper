<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\EventListener\MapFromListener;
use AutoMapper\EventListener\MapToContextListener;
use AutoMapper\EventListener\MapToListener;
use AutoMapper\EventListener\Symfony\AdvancedNameConverterListener;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(MapToContextListener::class)
            ->args([service('automapper.property_info.reflection_extractor')])
            ->tag('kernel.event_listener', ['event' => PropertyMetadataEvent::class, 'priority' => 64])
        ->set(MapToListener::class)
            ->args([service(PropertyTransformerRegistry::class)])
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 64])
        ->set(MapFromListener::class)
            ->args([service(PropertyTransformerRegistry::class)])
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 32])

        ->set(AdvancedNameConverterListener::class)
            ->args([service(AdvancedNameConverterInterface::class)])
    ;
};
