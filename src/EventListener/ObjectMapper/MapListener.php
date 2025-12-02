<?php

declare(strict_types=1);

namespace AutoMapper\EventListener\ObjectMapper;

use AutoMapper\AttributeReference\Reference;
use AutoMapper\Exception\BadMapDefinitionException;
use AutoMapper\Transformer\CallableTransformer;
use AutoMapper\Transformer\ExpressionLanguageTransformer;
use AutoMapper\Transformer\ReferenceTransformer;
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

    protected function getTransformerFromMapAttribute(string $class, Map $attribute, Reference $reference, bool $fromSource = true): ?TransformerInterface
    {
        $transformer = null;
        $transformerCallable = $attribute->transform;

        if ($transformerCallable !== null) {
            $callableName = null;

            if ($transformerCallable instanceof \Closure) {
                $transformer = new ReferenceTransformer($reference, true);
            } elseif (!\is_object($transformerCallable) && \is_callable($transformerCallable, false, $callableName)) {
                $transformer = new CallableTransformer($callableName);
            } elseif (\is_callable($transformerCallable, false, $callableName)) {
                $transformer = new ReferenceTransformer($reference, true);
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
