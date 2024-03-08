<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\Shared\CachedReflectionStatementsGenerator;
use AutoMapper\MapperGeneratorMetadataInterface;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * @internal
 */
final readonly class MapperConstructorGenerator
{
    public function __construct(
        private CachedReflectionStatementsGenerator $cachedReflectionStatementsGenerator
    ) {
    }

    /**
     * @return list<Stmt>
     */
    public function getStatements(MapperGeneratorMetadataInterface $mapperMetadata): array
    {
        $constructStatements = [];

        foreach ($mapperMetadata->getPropertiesMapping() as $propertyMapping) {
            $constructStatements[] = $this->extractCallbackForProperty($propertyMapping);
            $constructStatements[] = $this->hydrateCallbackForProperty($propertyMapping);
        }

        $constructStatements[] = $this->cachedReflectionStatementsGenerator->mapperConstructorStatement($mapperMetadata);

        return array_values(array_filter($constructStatements));
    }

    /**
     * Add read callback to the constructor of the generated mapper.
     *
     * ```php
     * $this->extractCallbacks['propertyName'] = $extractCallback;
     * ```
     */
    private function extractCallbackForProperty(PropertyMapping $propertyMapping): ?Stmt\Expression
    {
        $mapperMetadata = $propertyMapping->mapperMetadata;

        $extractCallback = $propertyMapping->readAccessor?->getExtractCallback($mapperMetadata->getSource());

        if (!$extractCallback) {
            return null;
        }

        return new Stmt\Expression(
            new Expr\Assign(
                new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'extractCallbacks'), new Scalar\String_($propertyMapping->property)),
                $extractCallback
            ));
    }

    /**
     * Add hydrate callback to the constructor of the generated mapper.
     *
     * ```php
     * $this->hydrateCallback['propertyName'] = $hydrateCallback;
     * ```
     */
    private function hydrateCallbackForProperty(PropertyMapping $propertyMapping): ?Stmt\Expression
    {
        $mapperMetadata = $propertyMapping->mapperMetadata;

        $hydrateCallback = $propertyMapping->writeMutator?->getHydrateCallback($mapperMetadata->getTarget());

        if (!$hydrateCallback) {
            return null;
        }

        return new Stmt\Expression(new Expr\Assign(
            new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'hydrateCallbacks'), new Scalar\String_($propertyMapping->property)),
            $hydrateCallback
        ));
    }
}
