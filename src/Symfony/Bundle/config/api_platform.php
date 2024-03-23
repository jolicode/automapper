<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\EventListener\ApiPlatform\JsonLdListener;
use AutoMapper\Transformer\ApiPlatform\JsonLdIdTransformer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(JsonLdListener::class)
            ->args([
                service('api_platform.resource_class_resolver'),
                service('api_platform.metadata.resource.metadata_collection_factory'),
            ])
            ->tag('kernel.event_listener', ['event' => GenerateMapperEvent::class, 'priority' => 0])

        ->set(JsonLdIdTransformer::class)
            ->args([service('api_platform.iri_converter')])
            ->tag('automapper.property_transformer', ['priority' => 0])
    ;
};
