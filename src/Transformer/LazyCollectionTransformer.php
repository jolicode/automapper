<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Lazy\LazyCollection;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;

/**
 * Decorates a collection transformer to produce a {@see LazyCollection} when the mapping is
 * requested lazily (see {@see MapperContext::shouldLazyLoad()}), and to fall back to the regular
 * eager transformation otherwise.
 *
 * The lazy branch maps each element on demand, as it is pulled from the source. Unless streaming
 * is requested (see {@see MapperContext::shouldStream()}) the elements are buffered, so the target
 * collection stays re-iterable and countable.
 *
 * This is only selected for targets that can actually hold a non-array iterable (array-shaped
 * targets, or properties typed as `iterable`); concrete `array` properties keep the eager
 * transformer. Adder/remover targets, which cannot be produced as a single value, are delegated
 * to the eager transformer as well.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class LazyCollectionTransformer implements TransformerInterface, DependentTransformerInterface, CheckTypeInterface, \Stringable
{
    public function __construct(
        private AbstractArrayTransformer $eagerTransformer,
        private TransformerInterface $itemTransformer,
    ) {
    }

    public function getEagerTransformer(): AbstractArrayTransformer
    {
        return $this->eagerTransformer;
    }

    public function getItemTransformer(): TransformerInterface
    {
        return $this->itemTransformer;
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        // Adder/remover targets are populated element by element by side effect, there is no
        // single collection value to make lazy: keep the eager transformation.
        if ($propertyMapping->target->writeMutator?->isAdderRemover()) {
            return $this->eagerTransformer->transform($input, $target, $propertyMapping, $uniqueVariableScope, $source, $existingValue);
        }

        [$eagerOutput, $eagerStatements] = $this->eagerTransformer->transform($input, $target, $propertyMapping, $uniqueVariableScope, $source, $existingValue);

        $outputVar = new Expr\Variable($uniqueVariableScope->getUniqueName('lazyCollection'));
        $contextVar = new Expr\Variable('context');

        /*
         * ```php
         * if (MapperContext::shouldLazyLoad($context)) {
         *     $lazyCollection = new LazyCollection(function ($item, $key) use ($value, $context) {
         *         ... // per-item transformation
         *         return $output;
         *     }, $input ?? [], !MapperContext::shouldStream($context));
         * } else {
         *     ... // eager transformation
         *     $lazyCollection = $values;
         * }
         * ```
         */
        $lazyBranch = new Stmt\Expression(new Expr\Assign($outputVar, new Expr\New_(
            new Name\FullyQualified(LazyCollection::class),
            [
                new Arg($this->createMapItemClosure($target, $propertyMapping, $uniqueVariableScope, $source, $contextVar)),
                new Arg(new Expr\BinaryOp\Coalesce($input, new Expr\Array_())),
                new Arg(new Expr\BooleanNot(new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldStream', [new Arg($contextVar)]))),
            ]
        )));

        $eagerBranch = [...$eagerStatements, new Stmt\Expression(new Expr\Assign($outputVar, $eagerOutput))];

        $if = new Stmt\If_(
            new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'shouldLazyLoad', [new Arg($contextVar)]),
            [
                'stmts' => [$lazyBranch],
                'else' => new Stmt\Else_($eagerBranch),
            ]
        );

        return [$outputVar, [$if]];
    }

    private function createMapItemClosure(Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, Expr\Variable $contextVar): Expr\Closure
    {
        $itemVar = new Expr\Variable($uniqueVariableScope->getUniqueName('item'));
        $keyVar = new Expr\Variable($uniqueVariableScope->getUniqueName('itemKey'));

        [$itemOutput, $itemStatements] = $this->itemTransformer->transform($itemVar, $target, $propertyMapping, $uniqueVariableScope, $source);

        /** @var class-string<ClosureUse> $closureUseClass */
        $closureUseClass = class_exists(ClosureUse::class) ? ClosureUse::class : Arg::class;

        $uses = [new $closureUseClass($contextVar)];

        // The per-item transformation may reference the source root (e.g. MapFrom expressions).
        if ($source instanceof Expr\Variable) {
            $uses[] = new $closureUseClass($source);
        }

        return new Expr\Closure([
            'params' => [new Param($itemVar), new Param($keyVar)],
            'uses' => $uses,
            'stmts' => [...$itemStatements, new Stmt\Return_($itemOutput)],
        ]);
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): ?Expr
    {
        if ($this->eagerTransformer instanceof CheckTypeInterface) {
            return $this->eagerTransformer->getCheckExpression($input, $target, $propertyMapping, $uniqueVariableScope, $source);
        }

        return null;
    }

    public function getDependencies(): array
    {
        return $this->eagerTransformer->getDependencies();
    }

    public function __toString(): string
    {
        return \sprintf('%s<%s>', self::class, (string) $this->eagerTransformer);
    }
}
