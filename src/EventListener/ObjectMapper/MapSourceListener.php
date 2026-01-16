<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\ObjectMapper;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use Symfony\Component\ObjectMapper\Attribute\Map;

final readonly class MapSourceListener extends MapListener
{
    public function __invoke(GenerateMapperEvent $event): void
    {
        // only handle class to class mapping
        if (!$event->mapperMetadata->sourceReflectionClass) {
            return;
        }

        $mapAttribute = null;
        $hasAnyMapAttribute = false;

        foreach ($event->mapperMetadata->sourceReflectionClass->getAttributes(Map::class) as $sourceAttribute) {
            /** @var Map $attribute */
            $attribute = $sourceAttribute->newInstance();
            $hasAnyMapAttribute = true;

            if (!$attribute->target || $attribute->target === $event->mapperMetadata->target) {
                $mapAttribute = $attribute;
                break;
            }
        }

        // it means that there is at least one Map attribute but none match the current mapping
        if (!$mapAttribute && $hasAnyMapAttribute) {
            return;
        }

        // get all properties
        $properties = [];

        foreach ($event->mapperMetadata->sourceReflectionClass->getProperties() as $property) {
            foreach ($property->getAttributes(Map::class) as $propertyAttribute) {
                /** @var Map $attribute */
                $attribute = $propertyAttribute->newInstance();
                $propertyMetadata = new PropertyMetadataEvent(
                    /*
                     * public ?string $if = null,// @TODO
                     */
                    $event->mapperMetadata,
                    new SourcePropertyMetadata($property->getName()),
                    new TargetPropertyMetadata($attribute->target ?? $property->getName()),
                    transformer: $this->getTransformerFromMapAttribute($event->mapperMetadata->sourceReflectionClass->getName(), $attribute, true),
                );

                $ifCallableName = null;

                if ($attribute->if && is_callable($attribute->if, false, $ifCallableName)) {
                    $propertyMetadata->if = $ifCallableName;
                } elseif (is_string($attribute->if)) {
                    $propertyMetadata->if = $attribute->if;
                }

                $properties[] = $propertyMetadata;
            }
        }

        $event->properties = $properties;

        if ($mapAttribute?->transform) {
            $callableName = null;

            if (is_callable($mapAttribute->transform, false, $callableName)) {
                $event->provider = $callableName;
            }
        }

        // Stop propagation if any Map attribute is found
        if ($hasAnyMapAttribute || count($properties) > 0 || $mapAttribute) {
            $event->stopPropagation();
        }
    }
}
