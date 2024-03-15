<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * @internal
 */
final readonly class MapperConstructorGenerator
{
    public function __construct(
        private CachedReflectionStatementsGenerator $cachedReflectionStatementsGenerator,
    ) {
    }

    /**
     * @return list<Stmt>
     */
    public function getStatements(GeneratorMetadata $metadata): array
    {
        $constructStatements = [];

        foreach ($metadata->propertiesMetadata as $propertyMapping) {
            $constructStatements[] = $this->extractCallbackForProperty($metadata, $propertyMapping);
            $constructStatements[] = $this->extractIsNullCallbackForProperty($metadata, $propertyMapping);
            $constructStatements[] = $this->hydrateCallbackForProperty($metadata, $propertyMapping);
        }

        $constructStatements[] = $this->cachedReflectionStatementsGenerator->mapperConstructorStatement($metadata);

        return array_values(array_filter($constructStatements));
    }

    /**
     * Add read callback to the constructor of the generated mapper.
     *
     * ```php
     * $this->extractCallbacks['propertyName'] = $extractCallback;
     * ```
     */
    private function extractCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMapping): ?Stmt\Expression
    {
        $extractCallback = $propertyMapping->source->accessor?->getExtractCallback($metadata->mapperMetadata->source);

        if (!$extractCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractCallbacks'), new Scalar\String_($propertyMapping->source->name)),
                $extractCallback
            ));
    }

    /**
     * Add read callback to the constructor of the generated mapper.
     *
     * ```php
     * $this->extractIsNullCallbacks['propertyName'] = $extractIsNullCallback;
     * ```
     */
    private function extractIsNullCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMapping): ?Stmt\Expression
    {
        $extractNullCallback = $propertyMapping->source->accessor?->getExtractIsNullCallback($metadata->mapperMetadata->source);

        if (!$extractNullCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractIsNullCallbacks'), new Scalar\String_($propertyMapping->source->name)),
                $extractNullCallback
            ));
    }

    /**
     * Add hydrate callback to the constructor of the generated mapper.
     *
     * ```php
     * $this->hydrateCallback['propertyName'] = $hydrateCallback;
     * ```
     */
    private function hydrateCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMapping): ?Stmt\Expression
    {
        $hydrateCallback = $propertyMapping->target->writeMutator?->getHydrateCallback($metadata->mapperMetadata->target);

        if (!$hydrateCallback) {
            return null;
        }

        return new Stmt\Expression(new Expr\Assign(
            new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'hydrateCallbacks'), new Scalar\String_($propertyMapping->target->name)),
            $hydrateCallback
        ));
    }
}
