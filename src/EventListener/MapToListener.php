<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Exception\BadMapDefinitionException;
use AutoMapper\Transformer\CallableTransformer;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformer;
use AutoMapper\Transformer\CustomTransformer\CustomModelTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformer;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\CustomTransformersRegistry;

final readonly class MapToListener
{
    public function __construct(private CustomTransformersRegistry $customTransformersRegistry)
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
                    $callableName = null;

                    if (\is_string($mapToAttributeInstance->transformer) && $customTransformer = $this->customTransformersRegistry->getCustomTransformer($mapToAttributeInstance->transformer)) {
                        if ($customTransformer instanceof CustomModelTransformerInterface) {
                            $transformer = new CustomModelTransformer($mapToAttributeInstance->transformer);
                        }

                        if ($customTransformer instanceof CustomPropertyTransformerInterface) {
                            $transformer = new CustomPropertyTransformer($mapToAttributeInstance->transformer);
                        }
                    } elseif (@\is_callable($mapToAttributeInstance->transformer, false, $callableName) && $callableName !== null) {
                        $transformer = new CallableTransformer($callableName);
                    }
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
                    throw new BadMapDefinitionException(sprintf('There is already a MapTo attribute with target "%s" in class "%s".', $property->target->name, $event->mapperMetadata->source));
                }

                $event->properties[$property->target->name] = $property;
            }
        }
    }
}
