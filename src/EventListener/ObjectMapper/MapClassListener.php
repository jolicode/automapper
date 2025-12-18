<?php

namespace AutoMapper\EventListener\ObjectMapper;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata;
use AutoMapper\Event\TargetPropertyMetadata;
use AutoMapper\Exception\BadMapDefinitionException;
use AutoMapper\Transformer\CallableTransformer;
use AutoMapper\Transformer\ExpressionLanguageTransformer;
use AutoMapper\Transformer\ServiceLocatorTransformer;
use AutoMapper\Transformer\TransformerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

final readonly class MapClassListener
{
    public function __construct(
        private ExpressionLanguage $expressionLanguage,
    ) {
    }

    public function __invoke(GenerateMapperEvent $event): void
    {
        // only handle class to class mapping
        if (!$event->mapperMetadata->sourceReflectionClass && !$event->mapperMetadata->targetReflectionClass) {
            return;
        }

        $mapAttribute = null;
        $reflectionClass = null;
        $isSource = false;

        if ($event->mapperMetadata->sourceReflectionClass) {
            foreach ($event->mapperMetadata->sourceReflectionClass->getAttributes(Map::class) as $sourceAttribute) {
                /** @var Map $attribute */
                $attribute = $sourceAttribute->newInstance();

                if (!$attribute->target || $attribute->target === $event->mapperMetadata->target) {
                    $mapAttribute = $attribute;
                    $reflectionClass = $event->mapperMetadata->sourceReflectionClass;
                    $isSource = true;
                    break;
                }
            }
        }

        if (!$mapAttribute && $event->mapperMetadata->targetReflectionClass) {
            foreach ($event->mapperMetadata->targetReflectionClass->getAttributes(Map::class) as $targetAttribute) {
                /** @var Map $attribute */
                $attribute = $targetAttribute->newInstance();

                if (!$attribute->source || $attribute->source === $event->mapperMetadata->source) {
                    $mapAttribute = $attribute;
                    $reflectionClass = $event->mapperMetadata->targetReflectionClass;
                    break;
                }
            }
        }

        if (!$mapAttribute || !$reflectionClass) {
            return;
        }

        // get all properties
        $properties = [];

        foreach ($reflectionClass->getProperties() as $property) {
            foreach ($property->getAttributes(Map::class) as $propertyAttribute) {
                /** @var Map $attribute */
                $attribute = $propertyAttribute->newInstance();
                $propertyMetadata = new PropertyMetadataEvent(
                /**
                 * public ?string $if = null,// @TODO
                 */
                    $event->mapperMetadata,
                    new SourcePropertyMetadata($isSource ? $property->getName() : ($attribute->source ?? $property->getName())),
                    new TargetPropertyMetadata($isSource ? ($attribute->target ?? $property->getName()) : $property->getName()),
                    transformer: $this->getTransformerFromMapAttribute($reflectionClass->getName(), $attribute, $isSource),
                    if: $attribute->if,
                );

                $properties[] = $propertyMetadata;
            }
        }

        $event->properties = $properties;

        if ($mapAttribute->transform) {
            $event->provider = $mapAttribute->transform;
        }
    }

    protected function getTransformerFromMapAttribute(string $class, Map $attribute, bool $fromSource = true): ?TransformerInterface
    {
        $transformer = null;

        if ($attribute->transform !== null) {
            $callableName = null;
            $transformerCallable = $attribute->transform;

            if ($transformerCallable instanceof \Closure) {
                // This is not supported because we cannot generate code from a closure
                // However this should never be possible since attributes does not allow to pass a closure
                // Let's keep this check for future proof
                throw new BadMapDefinitionException('Closure transformer is not supported.');
            }

            if (\is_callable($transformerCallable, false, $callableName)) {
                $transformer = new CallableTransformer($callableName);
            } elseif (\is_string($transformerCallable) && method_exists($class, $transformerCallable)) {
                $reflMethod = new \ReflectionMethod($class, $transformerCallable);

                if ($reflMethod->isStatic()) {
                    $transformer = new CallableTransformer($class . '::' . $transformerCallable);
                } else {
                    $transformer = new CallableTransformer($transformerCallable, $fromSource, !$fromSource);
                }
            } elseif (\is_string($transformerCallable) && class_exists($transformerCallable) && is_subclass_of($transformerCallable, TransformCallableInterface::class)) {
                $transformer = new ServiceLocatorTransformer($transformerCallable);
            } elseif (\is_string($transformerCallable)) {
                try {
                    $expression = $this->expressionLanguage->compile($transformerCallable, ['value' => 'source', 'context']);
                } catch (SyntaxError $e) {
                    throw new BadMapDefinitionException(\sprintf('Transformer "%s" targeted by %s transformer on class "%s" is not valid.', $transformerCallable, $attribute::class, $class), 0, $e);
                }

                $transformer = new ExpressionLanguageTransformer($expression);
            } else {
                throw new BadMapDefinitionException(\sprintf('Callable "%s" targeted by %s transformer on class "%s" is not valid.', json_encode($transformerCallable), $attribute::class, $class));
            }
        }

        return $transformer;
    }
}