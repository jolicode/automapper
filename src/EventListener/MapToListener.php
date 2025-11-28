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
                    if ($mapToAttributeInstance->property === null) {
                        throw new BadMapDefinitionException(\sprintf('Required `property` property in the "%s" attribute on "%s" class.', MapTo::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    if ($mapToAttributeInstance->transformer === null) {
                        throw new BadMapDefinitionException(\sprintf('Required `transformer` property in the "%s" attribute on "%s" class.', MapTo::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    $property = $mapToAttributeInstance->property;
                } elseif ($reflectionClassOrPropertyOrMethod instanceof \ReflectionMethod) {
                    $property = $this->getPropertyName($reflectionClassOrPropertyOrMethod->getName(), $properties);
                } else {
                    $property = $reflectionClassOrPropertyOrMethod->getName();
                }

                if (null === $property) {
                    $property = $reflectionClassOrPropertyOrMethod->getName();
                }

                $this->addPropertyFromSource($event, $mapToAttributeInstance, $property);
            }
        }
    }

    private function addPropertyFromSource(GenerateMapperEvent $event, MapTo $mapTo, string $property): void
    {
        $targets = null === $mapTo->target ? null : (\is_array($mapTo->target) ? $mapTo->target : [$mapTo->target]);

        if ($targets !== null && !\in_array($event->mapperMetadata->target, $targets, true)) {
            return;
        }

        $sourceProperty = new SourcePropertyMetadata($property, type: $mapTo->sourceType);
        $targetProperty = new TargetPropertyMetadata($mapTo->property ?? $property, type: $mapTo->targetType);

        $propertyMetadata = new PropertyMetadataEvent(
            mapperMetadata: $event->mapperMetadata,
            source: $sourceProperty,
            target: $targetProperty,
            maxDepth: $mapTo->maxDepth,
            transformer: $this->getTransformerFromMapAttribute($event->mapperMetadata->source, $mapTo),
            dateTimeFormat: $mapTo->dateTimeFormat,
            ignored: $mapTo->ignore,
            ignoreReason: $mapTo->ignore === true ? 'Property is ignored by MapTo Attribute on Source' : null,
            if: $mapTo->if,
            groups: $mapTo->groups,
            priority: $mapTo->priority,
            extractTypesFromGetter: $mapTo->extractTypesFromGetter,
            identifier: $mapTo->identifier,
        );

        if (\array_key_exists($propertyMetadata->target->property, $event->properties) && $event->properties[$propertyMetadata->target->property]->priority >= $propertyMetadata->priority) {
            return;
        }

        $event->properties[$propertyMetadata->target->property] = $propertyMetadata;
    }
}
