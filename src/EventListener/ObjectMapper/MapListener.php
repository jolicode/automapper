<?php

declare(strict_types=1);

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

abstract readonly class MapListener
{
    public function __construct(
        private ExpressionLanguage $expressionLanguage,
    ) {
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
