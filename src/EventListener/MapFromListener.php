<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Exception\BadMapDefinitionException;

/**
 * @internal
 */
final readonly class MapFromListener extends MapListener
{
    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->targetReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->targetReflectionClass->getProperties();
        $methods = $event->mapperMetadata->targetReflectionClass->getMethods();

        foreach ([$event->mapperMetadata->targetReflectionClass, ...$properties, ...$methods] as $reflectionClassOrPropertyOrMethod) {
            $mapFromAttributes = $reflectionClassOrPropertyOrMethod->getAttributes(MapFrom::class);

            if (0 === \count($mapFromAttributes)) {
                continue;
            }

            foreach ($mapFromAttributes as $mapFromAttribute) {
                /** @var MapFrom $mapFromAttributeInstance */
                $mapFromAttributeInstance = $mapFromAttribute->newInstance();
                $property = null;

                if ($reflectionClassOrPropertyOrMethod instanceof \ReflectionClass) {
                    if ($mapFromAttributeInstance->property === null) {
                        throw new BadMapDefinitionException(sprintf('Required `name` property in the "%s" attribute on "%s" class.', MapFrom::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    if ($mapFromAttributeInstance->transformer === null) {
                        throw new BadMapDefinitionException(sprintf('Required `transformer` property in the "%s" attribute on "%s" class.', MapFrom::class, $reflectionClassOrPropertyOrMethod->getName()));
                    }

                    $property = $mapFromAttributeInstance->property;
                } elseif ($reflectionClassOrPropertyOrMethod instanceof \ReflectionMethod) {
                    $property = $this->getPropertyName($reflectionClassOrPropertyOrMethod->getName(), $properties);
                }

                if (null === $property) {
                    $property = $reflectionClassOrPropertyOrMethod->getName();
                }

                $this->addPropertyFromTarget($event, $mapFromAttributeInstance, $property);
            }
        }
    }

    private function addPropertyFromTarget(GenerateMapperEvent $event, MapFrom $mapFrom, string $property): void
    {
        if ($mapFrom->source !== null && $event->mapperMetadata->source !== $mapFrom->source) {
            return;
        }

        $sourceProperty = new SourcePropertyMetadata($mapFrom->property ?? $property);
        $targetProperty = new TargetPropertyMetadata($property);

        $property = new PropertyMetadataEvent(
            mapperMetadata: $event->mapperMetadata,
            source: $sourceProperty,
            target: $targetProperty,
            maxDepth: $mapFrom->maxDepth,
            transformer: $this->getTransformerFromMapAttribute($event->mapperMetadata->target, $mapFrom, false),
            ignored: $mapFrom->ignore,
            ignoreReason: $mapFrom->ignore === true ? 'Property is ignored by MapFrom Attribute on Target' : null,
            if: $mapFrom->if,
            groups: $mapFrom->groups,
        );

        if (\array_key_exists($property->target->property, $event->properties)) {
            throw new BadMapDefinitionException(sprintf('There is already a MapTo or MapFrom attribute with target "%s" in class "%s" or class "%s".', $property->target->property, $event->mapperMetadata->source, $event->mapperMetadata->target));
        }

        $event->properties[$property->target->property] = $property;
    }
}
