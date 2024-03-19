<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

/**
 * Transformer array decorator.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class ArrayTransformer extends AbstractArrayTransformer implements CheckTypeInterface
{
    /**
     * Assign the value by pushing it to the array.
     *
     * $values[] = $output;
     */
    protected function getAssignExpr(Expr $valuesVar, Expr $outputVar, Expr $loopKeyVar, bool $assignByRef): Expr
    {
        if ($assignByRef) {
            return new Expr\AssignRef(new Expr\ArrayDimFetch($valuesVar), $outputVar);
        }

        return new Expr\Assign(new Expr\ArrayDimFetch($valuesVar), $outputVar);
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): ?Expr
    {
        $isArrayCheck = new Expr\FuncCall(
            new Name('is_array'),
            [
                new Arg($input),
            ]
        );

        if ($this->itemTransformer instanceof CheckTypeInterface) {
            $itemCheck = $this->itemTransformer->getCheckExpression(new Expr\ArrayDimFetch($input, new Scalar\Int_(0)), $target, $propertyMapping, $uniqueVariableScope, $source);

            if ($itemCheck) {
                return new Expr\BinaryOp\BooleanAnd($isArrayCheck, new Expr\BinaryOp\BooleanOr(
                    new Expr\BinaryOp\Identical(new Scalar\Int_(0), new Expr\FuncCall(new Name('count'), [new Arg($input)])),
                    $itemCheck
                ));
            }
        }

        return $isArrayCheck;
    }
}
