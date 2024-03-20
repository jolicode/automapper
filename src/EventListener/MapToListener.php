<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Exception\BadMapDefinitionException;

/**
 * @internal
 */
final readonly class MapToListener extends MapListener
{
    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->sourceReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->sourceReflectionClass->getProperties();
        $methods = $event->mapperMetadata->sourceReflectionClass->getMethods();

        foreach ([$event->mapperMetadata->sourceReflectionClass, ...$properties, ...$methods] as $reflectionClassOrPropertyOrMethod) {
            $mapToAttributes = $reflectionClassOrPropertyOrMethod->getAttributes(MapTo::class);

            if (0 === \count($mapToAttributes)) {
                continue;
            }

            foreach ($mapToAttributes as $mapToAttribute) {
                /** @var MapTo $mapToAttributeInstance */
                $mapToAttributeInstance = $mapToAttribute->newInstance();

                if ($reflectionClassOrPropertyOrMethod instanceof \ReflectionClass) {
                    if ($mapToAttributeInstance->name === null) {
                        throw new BadMapDefinitionException(sprintf('Required `name` property in the "%s" attribute on "%s" class.', MapTo::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    if ($mapToAttributeInstance->transformer === null) {
                        throw new BadMapDefinitionException(sprintf('Required `transformer` property in the "%s" attribute on "%s" class.', MapTo::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    $name = $mapToAttributeInstance->name;
                } elseif ($reflectionClassOrPropertyOrMethod instanceof \ReflectionMethod) {
                    $name = $this->getPropertyName($reflectionClassOrPropertyOrMethod->getName(), $properties);
                } else {
                    $name = $reflectionClassOrPropertyOrMethod->getName();
                }

                if (null === $name) {
                    $name = $reflectionClassOrPropertyOrMethod->getName();
                }

                $this->addPropertyFromSource($event, $mapToAttributeInstance, $name);
            }
        }
    }

    private function addPropertyFromSource(GenerateMapperEvent $event, MapTo $mapTo, string $name): void
    {
        if ($mapTo->target !== null && $event->mapperMetadata->target !== $mapTo->target) {
            return;
        }

        $sourceProperty = new SourcePropertyMetadata($name);
        $targetProperty = new TargetPropertyMetadata($mapTo->name ?? $name);

        $property = new PropertyMetadataEvent(
            mapperMetadata: $event->mapperMetadata,
            source: $sourceProperty,
            target: $targetProperty,
            maxDepth: $mapTo->maxDepth,
            transformer: $this->getTransformerFromMapAttribute($event->mapperMetadata->source, $mapTo),
            ignored: $mapTo->ignore,
            if: $mapTo->if,
            groups: $mapTo->groups,
        );

        if (\array_key_exists($property->target->name, $event->properties)) {
            throw new BadMapDefinitionException(sprintf('There is already a MapTo attribute with target "%s" in class "%s".', $property->target->name, $event->mapperMetadata->source));
        }

        $event->properties[$property->target->name] = $property;
    }
}
