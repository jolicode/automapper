<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\EventListener\MapToContextListener;
use AutoMapper\EventListener\Symfony\AdvancedNameConverterListener;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(MapToContextListener::class)
            ->args([service('automapper.property_info.reflection_extractor')])
            ->tag('kernel.event_listener', ['event' => PropertyMetadataEvent::class, 'priority' => 64])

        ->set(AdvancedNameConverterListener::class)
            ->args([service(AdvancedNameConverterInterface::class)])
    ;
};
