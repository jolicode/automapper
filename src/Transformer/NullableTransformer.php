<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;

/**
 * Tansformer decorator to handle null values.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class NullableTransformer implements TransformerInterface, DependentTransformerInterface
{
    public function __construct(
        private TransformerInterface $itemTransformer,
        private bool $isTargetNullable,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        [$output, $itemStatements] = $this->itemTransformer->transform($input, $target, $propertyMapping, $uniqueVariableScope);

        $newOutput = null;
        $statements = [];
        $assignClass = ($this->itemTransformer instanceof AssignedByReferenceTransformerInterface && $this->itemTransformer->assignByRef()) ? Expr\AssignRef::class : Expr\Assign::class;

        if ($this->isTargetNullable) {
            /**
             * If target is nullable we set the default value to null, if not nullable there will no default value.
             *
             * $value = null;
             *
             * if ($input !== null) {
             *     ... // item statements
             *     $value = $output;
             * }
             *
             * // mutator statements
             */
            $newOutput = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));
            $statements[] = new Stmt\Expression(new Expr\Assign($newOutput, new Expr\ConstFetch(new Name('null'))));
            $itemStatements[] = new Stmt\Expression(new $assignClass($newOutput, $output));
        }

        if ($input instanceof Expr\ArrayDimFetch) {
            /*
             * if `$input` is an array access, let's validate if the array key exists and is not null:
             *
             * if (isset($value['key'])) {
             */
            $statements[] = new Stmt\If_(new Expr\Isset_([$input]), ['stmts' => $itemStatements]);
        } else {
            /*
             * otherwise, let's check the value is not null:
             *
             *  if ($input !== null) {
             */
            $statements[] = new Stmt\If_(new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('null')), $input), ['stmts' => $itemStatements]);
        }

        return [$newOutput ?? $output, $statements];
    }

    public function getDependencies(): array
    {
        if (!$this->itemTransformer instanceof DependentTransformerInterface) {
            return [];
        }

        return $this->itemTransformer->getDependencies();
    }
}
