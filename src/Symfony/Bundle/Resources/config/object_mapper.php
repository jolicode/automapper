<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\AutoMapper;
use AutoMapper\AutoMapperInterface;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\EventListener\ObjectMapper\MapSourceListener;
use AutoMapper\EventListener\ObjectMapper\MapTargetListener;
use AutoMapper\ObjectMapper\ObjectMapper;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(MapSourceListener::class)
            ->args([
                service('automapper.mapper_service_locator'),
                service('automapper.expression_language'),
            ])
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 0])

        ->set(MapTargetListener::class)
            ->args([
                service('automapper.mapper_service_locator'),
                service('automapper.expression_language'),
            ])
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 0])

        ->set('automapper.object_mapper')
            ->class(ObjectMapper::class)
            ->args([
                service(AutoMapperInterface::class),
                service('automapper.mapper_service_locator'),
            ])

        ->set(ObjectMapperInterface::class)
            ->alias(ObjectMapperInterface::class, 'automapper.object_mapper')
            ->public()
    ;
};
