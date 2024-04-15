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

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            $constructStatements[] = $this->extractCallbackForProperty($metadata, $propertyMetadata);
            $constructStatements[] = $this->extractIsNullCallbackForProperty($metadata, $propertyMetadata);
            $constructStatements[] = $this->hydrateCallbackForProperty($metadata, $propertyMetadata);
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
    private function extractCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Stmt\Expression
    {
        $extractCallback = $propertyMetadata->source->accessor?->getExtractCallback($metadata->mapperMetadata->source);

        if (!$extractCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractCallbacks'), new Scalar\String_($propertyMetadata->source->property)),
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
    private function extractIsNullCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Stmt\Expression
    {
        $extractNullCallback = $propertyMetadata->source->accessor?->getExtractIsNullCallback($metadata->mapperMetadata->source);

        if (!$extractNullCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractIsNullCallbacks'), new Scalar\String_($propertyMetadata->source->property)),
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
    private function hydrateCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Stmt\Expression
    {
        $hydrateCallback = $propertyMetadata->target->writeMutator?->getHydrateCallback($metadata->mapperMetadata->target);

        if (!$hydrateCallback) {
            return null;
        }

        return new Stmt\Expression(new Expr\Assign(
            new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'hydrateCallbacks'), new Scalar\String_($propertyMetadata->target->property)),
            $hydrateCallback
        ));
    }
}
