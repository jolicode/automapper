<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\ObjectMapper;

use AutoMapper\AttributeReference\Reference;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Metadata\Provider;
use Symfony\Component\ObjectMapper\Attribute\Map;

final readonly class MapTargetListener extends MapListener
{
    public function __invoke(GenerateMapperEvent $event): void
    {
        // only handle class to class mapping
        if (!$event->mapperMetadata->targetReflectionClass) {
            return;
        }

        $mapAttribute = null;
        $hasAnyMapAttribute = false;

        foreach ($event->mapperMetadata->targetReflectionClass->getAttributes(Map::class) as $targetAttribute) {
            /** @var Map $attribute */
            $attribute = $targetAttribute->newInstance();
            $hasAnyMapAttribute = true;

            if (!$attribute->source || $attribute->source === $event->mapperMetadata->source) {
                $mapAttribute = $attribute;
            }
        }

        // it means that there is at least one Map attribute but none match the current mapping
        if (!$mapAttribute && $hasAnyMapAttribute) {
            return;
        }

        // get all properties
        $properties = [];

        foreach ($event->mapperMetadata->targetReflectionClass->getProperties() as $property) {
            foreach ($property->getAttributes(Map::class) as $index => $propertyAttribute) {
                /** @var Map $attribute */
                $attribute = $propertyAttribute->newInstance();
                $reference = new Reference(Map::class, $index, $event->mapperMetadata->targetReflectionClass->getName(), propertyName: $property->getName());
                $propertyMetadata = new PropertyMetadataEvent(
                    /*
                     * public ?string $if = null,// @TODO
                     */
                    $event->mapperMetadata,
                    new SourcePropertyMetadata($attribute->source ?? $property->getName()),
                    new TargetPropertyMetadata($property->getName()),
                    transformer: $this->getTransformerFromMapAttribute($event->mapperMetadata->targetReflectionClass->getName(), $attribute, $reference, false),
                );

                $ifCallableName = null;

                if ($attribute->if && \is_callable($attribute->if, false, $ifCallableName)) {
                    $propertyMetadata->if = $ifCallableName;
                } elseif (\is_string($attribute->if)) {
                    $propertyMetadata->if = $attribute->if;
                }

                $properties[] = $propertyMetadata;
            }
        }

        $event->properties = [...$event->properties, ...$properties];

        if ($mapAttribute?->transform) {
            $callableName = null;

            if (\is_callable($mapAttribute->transform, false, $callableName)) {
                $event->provider = new Provider(Provider::TYPE_CALLABLE, $callableName, true);
            }

            if (\is_string($mapAttribute->transform) && $this->serviceLocator->has($mapAttribute->transform)) {
                $event->provider = new Provider(Provider::TYPE_SERVICE_CALLABLE, $mapAttribute->transform, true);
            }
        }
    }
}
