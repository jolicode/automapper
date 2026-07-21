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
use Symfony\Component\ObjectMapper\Condition\TargetClass;

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

            if (!$attribute->target || $attribute->target === $event->mapperMetadata->target || is_subclass_of($event->mapperMetadata->target, $attribute->target)) {
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
            foreach ($property->getAttributes(Map::class) as $index => $propertyAttribute) {
                /** @var Map $attribute */
                $attribute = $propertyAttribute->newInstance();
                $reference = new Reference(Map::class, $index, $event->mapperMetadata->sourceReflectionClass->getName(), propertyName: $property->getName());
                $propertyMetadata = new PropertyMetadataEvent(
                    /*
                     * public ?string $if = null,// @TODO
                     */
                    $event->mapperMetadata,
                    new SourcePropertyMetadata($property->getName()),
                    new TargetPropertyMetadata($attribute->target ?? $property->getName()),
                    transformer: $this->getTransformerFromMapAttribute($event->mapperMetadata->sourceReflectionClass->getName(), $attribute, $reference, true),
                );

                if (false === $attribute->if) {
                    // symfony/object-mapper never maps a property with `if: false`
                    $propertyMetadata->ignored = true;
                    $propertyMetadata->ignoreReason = 'Property is ignored by Map Attribute if condition';
                } elseif ($attribute->if instanceof TargetClass) {
                    $reflectionObject = new \ReflectionClass($attribute->if);

                    if ($reflectionObject->hasProperty('className')) {
                        /** @var string $targetClassName */
                        $targetClassName = $reflectionObject->getProperty('className')->getRawValue($attribute->if);

                        if (
                            $targetClassName !== null
                            && $event->mapperMetadata->target !== $targetClassName
                            && !is_subclass_of($event->mapperMetadata->target, $targetClassName)
                        ) {
                            continue;
                        }
                    }

                    if ($reflectionObject->hasProperty('targets')) {
                        /** @var string[] $targets */
                        $targets = $reflectionObject->getProperty('targets')->getRawValue($attribute->if);
                        $anyTrue = false;

                        foreach ($targets as $targetClassName) {
                            if ($event->mapperMetadata->target === $targetClassName
                                || is_subclass_of($event->mapperMetadata->target, $targetClassName)
                            ) {
                                $anyTrue = true;
                                break;
                            }
                        }

                        if (!$anyTrue) {
                            continue;
                        }
                    }
                } elseif ($attribute->if && \is_callable($attribute->if, false)) {
                    // symfony/object-mapper callables have their own calling convention, keep the
                    // attribute reference so the generated code can replicate it
                    $propertyMetadata->if = $reference;
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

        // Stop propagation if any Map attribute is found
        if ($hasAnyMapAttribute || \count($properties) > 0 || $mapAttribute) {
            $event->stopPropagation();
        }
    }
}
