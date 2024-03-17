<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\Metadata\GeneratorMetadata;
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
    public function createTargetStatement(GeneratorMetadata $metadata): ?Stmt
    {
        if (!$this->supports($metadata)) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;

        if ($metadata->isTargetCloneable()) {
            return new Stmt\Expression(
                new Expr\Assign($variableRegistry->getResult(), new Expr\Clone_(new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget')))
            );
        }

        return new Stmt\Expression(new Expr\Assign($variableRegistry->getResult(), new Expr\MethodCall(
            new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
            'newInstanceWithoutConstructor'
        )));
    }

    public function mapperConstructorStatement(GeneratorMetadata $metadata): ?Stmt\Expression
    {
        if (!$this->supports($metadata)) {
            return null;
        }

        if ($metadata->isTargetCloneable()) {
            return new Stmt\Expression(new Expr\Assign(
                new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
                new Expr\MethodCall(new Expr\New_(new Name\FullyQualified(\ReflectionClass::class), [
                    new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
                ]), 'newInstanceWithoutConstructor')
            ));
        }

        return new Stmt\Expression(new Expr\Assign(
            new Expr\PropertyFetch(new Expr\Variable('this'), 'cachedTarget'),
            new Expr\New_(new Name\FullyQualified(\ReflectionClass::class), [
                new Arg(new Scalar\String_($metadata->mapperMetadata->target)),
            ])
        ));
    }

    private function supports(GeneratorMetadata $metadata): bool
    {
        if (!$metadata->isTargetUserDefined()) {
            return false;
        }

        if ($metadata->mapperMetadata->lazyGhostClassName !== null) {
            return true;
        }

        $targetConstructor = $metadata->mapperMetadata->targetReflectionClass?->getConstructor();

        return $targetConstructor !== null;
    }
}
