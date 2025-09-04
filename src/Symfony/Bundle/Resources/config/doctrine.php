<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\EventListener\Doctrine\DoctrineIdentifierListener;
use AutoMapper\EventListener\Doctrine\DoctrineProviderListener;
use AutoMapper\Provider\Doctrine\DoctrineProvider;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(DoctrineIdentifierListener::class)
            ->args([service('automapper.doctrine.object_manager')])
            ->tag('kernel.event_listener', ['event' => PropertyMetadataEvent::class, 'priority' => 0])

        ->set(DoctrineProviderListener::class)
            ->args([service('automapper.doctrine.object_manager')])
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 0])

        ->set(DoctrineProvider::class)
            ->args([service('automapper.doctrine.object_manager')])
            ->tag('automapper.provider', ['priority' => 0])
    ;
};
