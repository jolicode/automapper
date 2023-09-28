<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use PhpParser\Node\Expr;

/**
 * Transformer array decorator.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final readonly class ArrayTransformer extends AbstractArrayTransformer
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
}
