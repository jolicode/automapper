<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\MapperMetadata\MapperGeneratorMetadataInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * This class creates both "constructor arguments" and "create target" statements for cached reflection target,
 * because these things are closely linked.
 *
 * ---
 *
 * When the target is cloneable and has a constructor which is not usable,
 * we clone a cached version of the target created with reflection to improve performance.
 *
 * ```php
 * // constructor of mapper
 * $this->cachedTarget = (new \ReflectionClass(Foo:class))->newInstanceWithoutConstructor();
 *
 * // map method
 * $result = clone $this->cachedTarget;
 * ```
 *
 * ---
 *
 * When the target does not have a constructor and is not cloneable, we cache the reflection class to improve performance.
 *
 * ```php
 * // constructor of mapper
 * $this->cachedTarget = (new \ReflectionClass(Foo:class));
 *
 * // map method
 * $result = $this->cachedTarget->newInstanceWithoutConstructor();
 * ```
 *
 * @internal
 */
final readonly class CachedReflectionStatementsGenerator
{
    public function createTargetStatement(MapperGeneratorMetadataInterface $mapperMetadata): Stmt|null
    {
        if (!$this->supports($mapperMetadata)) {
            return null;
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        if ($mapperMetadata->isTargetCloneable()) {
            return new Stmt\Expression(
                new Expr\Assign($variableRegistry->getResult(), new Expr\Clone_(new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget')))
            );
        }

        return new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\MethodCall(
            new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
            'newInstanceWithoutConstructor'
        )));
    }

    public function mapperConstructorStatement(MapperGeneratorMetadataInterface $mapperMetadata): Stmt\Expression|null
    {
        if (!$this->supports($mapperMetadata)) {
            return null;
        }

        if ($mapperMetadata->isTargetCloneable()) {
            return new Stmt\Expression(new Expr\Assign(
                new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
                new Expr\MethodCall(new Expr\New_(new Name\FullyQualified(\ReflectionClass::class), [
                    new Arg(new Scalar\String_($mapperMetadata->getTarget())),
                ]), 'newInstanceWithoutConstructor')
            ));
        }

        return new Stmt\Expression(new Expr\Assign(
            new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
            new Expr\New_(new Name\FullyQualified(\ReflectionClass::class), [
                new Arg(new Scalar\String_($mapperMetadata->getTarget())),
            ])
        ));
    }

    private function supports(MapperGeneratorMetadataInterface $mapperMetadata): bool
    {
        if (!$mapperMetadata->targetIsAUserDefinedClass()) {
            return false;
        }

        $targetConstructor = $mapperMetadata->getCachedTargetReflectionClass()?->getConstructor();

        return $targetConstructor && !$mapperMetadata->hasConstructor();
    }
}
