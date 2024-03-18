<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapFrom;
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
use AutoMapper\Transformer\TransformerInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;

final readonly class MapToListener
{
    public function __construct(
        private CustomTransformersRegistry $customTransformersRegistry,
        private InflectorInterface $inflector = new EnglishInflector(),
    ) {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->sourceReflectionClass === null) {
            return;
        }

        $this->setPropertiesFromSource($event);
        $this->setPropertiesFromTarget($event);
    }

    private function setPropertiesFromSource(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->sourceReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->sourceReflectionClass->getProperties();

        foreach ($properties as $reflectionProperty) {
            $mapToAttributes = $reflectionProperty->getAttributes(MapTo::class);

            if (0 === \count($mapToAttributes)) {
                continue;
            }

            foreach ($mapToAttributes as $mapToAttribute) {
                /** @var MapTo $mapToAttributeInstance */
                $mapToAttributeInstance = $mapToAttribute->newInstance();

                $this->addPropertyFromSource($event, $mapToAttributeInstance, $reflectionProperty->getName());
            }
        }

        $methods = $event->mapperMetadata->sourceReflectionClass->getMethods();

        foreach ($methods as $reflectionMethod) {
            $mapToAttributes = $reflectionMethod->getAttributes(MapTo::class);

            if (0 === \count($mapToAttributes)) {
                continue;
            }

            foreach ($mapToAttributes as $mapToAttribute) {
                /** @var MapTo $mapToAttributeInstance */
                $mapToAttributeInstance = $mapToAttribute->newInstance();
                $name = $this->getPropertyName($reflectionMethod->getName(), $properties);

                if (null === $name) {
                    $name = $reflectionMethod->getName();
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
            $event->mapperMetadata,
            $sourceProperty,
            $targetProperty,
            $mapTo->maxDepth,
            $this->getTransformerFromMapAttribute($event->mapperMetadata->source, $mapTo),
            $mapTo->ignore,
        );

        if (\array_key_exists($property->target->name, $event->properties)) {
            throw new BadMapDefinitionException(sprintf('There is already a MapTo attribute with target "%s" in class "%s".', $property->target->name, $event->mapperMetadata->source));
        }

        $event->properties[$property->target->name] = $property;
    }

    private function setPropertiesFromTarget(GenerateMapperEvent $event): void
    {
        if ($event->mapperMetadata->targetReflectionClass === null) {
            return;
        }

        $properties = $event->mapperMetadata->targetReflectionClass->getProperties();

        foreach ($properties as $reflectionProperty) {
            $mapFromAttributes = $reflectionProperty->getAttributes(MapFrom::class);

            if (0 === \count($mapFromAttributes)) {
                continue;
            }

            foreach ($mapFromAttributes as $mapFromAttribute) {
                /** @var MapFrom $mapFromAttributeInstance */
                $mapFromAttributeInstance = $mapFromAttribute->newInstance();

                $this->addPropertyFromTarget($event, $mapFromAttributeInstance, $reflectionProperty->getName());
            }
        }

        $methods = $event->mapperMetadata->targetReflectionClass->getMethods();

        foreach ($methods as $reflectionMethod) {
            $mapFromAttributes = $reflectionMethod->getAttributes(MapFrom::class);

            if (0 === \count($mapFromAttributes)) {
                continue;
            }

            foreach ($mapFromAttributes as $mapFromAttribute) {
                /** @var MapFrom $mapFromAttributeInstance */
                $mapFromAttributeInstance = $mapFromAttribute->newInstance();
                $name = $this->getPropertyName($reflectionMethod->getName(), $properties);

                if (null === $name) {
                    $name = $reflectionMethod->getName();
                }

                $this->addPropertyFromTarget($event, $mapFromAttributeInstance, $name);
            }
        }
    }

    private function addPropertyFromTarget(GenerateMapperEvent $event, MapFrom $mapFrom, string $name): void
    {
        if ($mapFrom->source !== null && $event->mapperMetadata->source !== $mapFrom->source) {
            return;
        }

        $sourceProperty = new SourcePropertyMetadata($mapFrom->name ?? $name);
        $targetProperty = new TargetPropertyMetadata($name);

        $property = new PropertyMetadataEvent(
            $event->mapperMetadata,
            $sourceProperty,
            $targetProperty,
            $mapFrom->maxDepth,
            $this->getTransformerFromMapAttribute($event->mapperMetadata->target, $mapFrom),
            $mapFrom->ignore,
        );

        if (\array_key_exists($property->target->name, $event->properties)) {
            throw new BadMapDefinitionException(sprintf('There is already a MapTo or MapFrom attribute with target "%s" in class "%s" or class "%s".', $property->target->name, $event->mapperMetadata->source, $event->mapperMetadata->target));
        }

        $event->properties[$property->target->name] = $property;
    }

    private function getTransformerFromMapAttribute(string $class, MapTo|MapFrom $attribute): ?TransformerInterface
    {
        $transformer = null;

        if ($attribute->transformer !== null) {
            $callableName = null;
            $transformerCallable = $attribute->transformer;

            if ($transformerCallable instanceof \Closure) {
                // This is not supported because we cannot generate code from a closure
                // However this should never be possible since attributes does not allow to pass a closure
                // Let's keep this check for future proof
                throw new BadMapDefinitionException('Closure transformer is not supported.');
            }

            if (\is_string($transformerCallable) && $customTransformer = $this->customTransformersRegistry->getCustomTransformer($transformerCallable)) {
                if ($customTransformer instanceof CustomModelTransformerInterface) {
                    $transformer = new CustomModelTransformer($transformerCallable);
                }

                if ($customTransformer instanceof CustomPropertyTransformerInterface) {
                    $transformer = new CustomPropertyTransformer($transformerCallable);
                }
            } elseif (\is_callable($transformerCallable, false, $callableName)) {
                $transformer = new CallableTransformer($callableName);
            } elseif (\is_string($transformerCallable)) {
                // Check the method exist on the class
                if (!method_exists($class, $transformerCallable)) {
                    if (class_exists($transformerCallable)) {
                        throw new BadMapDefinitionException(sprintf('Transformer "%s" targeted by %s transformer does not exist on class "%s", did you register it ?.', $transformerCallable, $attribute::class, $class));
                    }

                    throw new BadMapDefinitionException(sprintf('Method "%s" targeted by %s transformer does not exist on class "%s".', $transformerCallable, $attribute::class, $class));
                }

                $reflMethod = new \ReflectionMethod($class, $transformerCallable);

                if ($reflMethod->isStatic()) {
                    $transformer = new CallableTransformer($class . '::' . $transformerCallable);
                } else {
                    $transformer = new CallableTransformer($transformerCallable, true);
                }
            } else {
                throw new BadMapDefinitionException(sprintf('Callable "%s" targeted by %s transformer on class "%s" is not valid.', json_encode($transformerCallable), $attribute::class, $class));
            }
        }

        return $transformer;
    }

    /**
     * @param \ReflectionProperty[] $reflectionProperties
     */
    private function getPropertyName(string $methodName, array $reflectionProperties): ?string
    {
        $pattern = implode('|', array_merge(ReflectionExtractor::$defaultAccessorPrefixes, ReflectionExtractor::$defaultMutatorPrefixes));

        if ('' !== $pattern && preg_match('/^(' . $pattern . ')(.+)$/i', $methodName, $matches)) {
            if (!\in_array($matches[1], ReflectionExtractor::$defaultArrayMutatorPrefixes)) {
                return lcfirst($matches[2]);
            }

            foreach ($reflectionProperties as $reflectionProperty) {
                foreach ($this->inflector->singularize($reflectionProperty->name) as $name) {
                    if (strtolower($name) === strtolower($matches[2])) {
                        return $reflectionProperty->name;
                    }
                }
            }

            return lcfirst($matches[2]);
        }

        return null;
    }
}
