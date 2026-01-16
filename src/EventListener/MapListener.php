<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\MapTo;
use AutoMapper\Exception\BadMapDefinitionException;
use AutoMapper\Transformer\CallableTransformer;
use AutoMapper\Transformer\ExpressionLanguageTransformer;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformer;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\TransformerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\Inflector\InflectorInterface;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;

/**
 * @internal
 */
abstract readonly class MapListener
{
    public function __construct(
        private ContainerInterface $serviceLocator,
        private ExpressionLanguage $expressionLanguage,
        private InflectorInterface $inflector = new EnglishInflector(),
        protected StringTypeResolver $stringTypeResolver = new StringTypeResolver(),
    ) {
    }

    protected function getTransformerFromMapAttribute(string $class, MapTo|MapFrom $attribute, bool $fromSource = true): ?TransformerInterface
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

            if (\is_string($transformerCallable) && $this->serviceLocator->has($transformerCallable) && ($customTransformer = $this->serviceLocator->get($transformerCallable)) && $customTransformer instanceof PropertyTransformerInterface) {
                $transformer = new PropertyTransformer($transformerCallable);
            } elseif (\is_callable($transformerCallable, false, $callableName)) {
                $transformer = new CallableTransformer($callableName);
            } elseif (\is_string($transformerCallable) && method_exists($class, $transformerCallable)) {
                $reflMethod = new \ReflectionMethod($class, $transformerCallable);

                if ($reflMethod->isStatic()) {
                    $transformer = new CallableTransformer($class . '::' . $transformerCallable);
                } else {
                    $transformer = new CallableTransformer($transformerCallable, $fromSource, !$fromSource);
                }
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

    /**
     * @param \ReflectionProperty[] $reflectionProperties
     */
    protected function getPropertyName(string $methodName, array $reflectionProperties): ?string
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
