<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\EventListener\MapFromListener;
use AutoMapper\EventListener\MapperListener;
use AutoMapper\EventListener\MapProviderListener;
use AutoMapper\EventListener\MapToContextListener;
use AutoMapper\EventListener\MapToListener;
use AutoMapper\EventListener\Symfony\NameConverterListener;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(MapToContextListener::class)
            ->args([service('automapper.property_info.reflection_extractor')])
            ->tag('kernel.event_listener', ['event' => PropertyMetadataEvent::class, 'priority' => 64])
        ->set(MapToListener::class)
            ->args([service('automapper.mapper_service_locator'), service('automapper.expression_language')])
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 64])
        ->set(MapFromListener::class)
            ->args([service('automapper.mapper_service_locator'), service('automapper.expression_language')])
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 32])
        ->set(MapProviderListener::class)
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 128])
        ->set(MapperListener::class)
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 256])

        ->set(NameConverterListener::class)
            ->args([service(NameConverterInterface::class)])
    ;
};
