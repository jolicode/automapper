<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;

class MapToMapperListener
{
    public function __construct()
    {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->sourceReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->sourceReflectionClass->getProperties();

        foreach ($properties as $reflectionProperty) {
            $mapToAttributes = $reflectionProperty->getAttributes(MapTo::class);

            if (empty($mapToAttributes)) {
                continue;
            }

            foreach ($mapToAttributes as $mapToAttribute) {
                /** @var MapTo $mapToAttributeInstance */
                $mapToAttributeInstance = $mapToAttribute->newInstance();

                if ($mapToAttributeInstance->target !== null && $event->mapperMetadata->target !== $mapToAttributeInstance->target) {
                    continue;
                }

                $sourceProperty = new SourcePropertyMetadata($reflectionProperty->getName());
                $targetProperty = new TargetPropertyMetadata($mapToAttributeInstance->name ?? $reflectionProperty->getName());

                $transformer = null;

                if ($mapToAttributeInstance->transformer !== null) {
                    // @TODO create transformer
                }

                $property = new PropertyMetadataEvent(
                    $event->mapperMetadata,
                    $sourceProperty,
                    $targetProperty,
                    $mapToAttributeInstance->maxDepth,
                    $transformer,
                    $mapToAttributeInstance->ignore,
                );

                if (isset($event->properties[$property->target->name])) {
                    // @TODO throw exception
                }

                $event->properties[$property->target->name] = $property;
            }
        }
    }
}
