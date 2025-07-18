<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\ReadAccessor;
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
            $constructStatements[] = $this->extractIsUndefinedCallbackForProperty($metadata, $propertyMetadata);
            $constructStatements[] = $this->extractTargetCallbackForProperty($metadata, $propertyMetadata);
            $constructStatements[] = $this->extractTargetIsNullCallbackForProperty($metadata, $propertyMetadata);
            $constructStatements[] = $this->extractTargetIsUndefinedCallbackForProperty($metadata, $propertyMetadata);
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
     * Add read callback to the constructor of the generated mapper.
     *
     * ```php
     * $this->extractIsUndefinedCallbacks['propertyName'] = $extractIsNullCallback;
     * ```
     */
    private function extractIsUndefinedCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Stmt\Expression
    {
        $extractUndefinedCallback = $propertyMetadata->source->accessor?->getExtractIsUndefinedCallback($metadata->mapperMetadata->source);

        if (!$extractUndefinedCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractIsUndefinedCallbacks'), new Scalar\String_($propertyMetadata->source->property)),
                $extractUndefinedCallback
            ));
    }

    /**
     * Add read callback to the constructor of the generated mapper.
     *
     * ```php
     * $this->extractCallbacks['propertyName'] = $extractCallback;
     * ```
     */
    private function extractTargetCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Stmt\Expression
    {
        $extractCallback = $propertyMetadata->target->readAccessor?->getExtractCallback($metadata->mapperMetadata->target);

        if (!$extractCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), ReadAccessor::EXTRACT_TARGET_CALLBACK), new Scalar\String_($propertyMetadata->target->property)),
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
    private function extractTargetIsNullCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Stmt\Expression
    {
        $extractNullCallback = $propertyMetadata->target->readAccessor?->getExtractIsNullCallback($metadata->mapperMetadata->target);

        if (!$extractNullCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), ReadAccessor::EXTRACT_TARGET_IS_NULL_CALLBACK), new Scalar\String_($propertyMetadata->target->property)),
                $extractNullCallback
            ));
    }

    /**
     * Add read callback to the constructor of the generated mapper.
     *
     * ```php
     * $this->extractIsUndefinedCallbacks['propertyName'] = $extractIsNullCallback;
     * ```
     */
    private function extractTargetIsUndefinedCallbackForProperty(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Stmt\Expression
    {
        $extractUndefinedCallback = $propertyMetadata->target->readAccessor?->getExtractIsUndefinedCallback($metadata->mapperMetadata->target);

        if (!$extractUndefinedCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), ReadAccessor::EXTRACT_TARGET_IS_UNDEFINED_CALLBACK), new Scalar\String_($propertyMetadata->target->property)),
                $extractUndefinedCallback
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
