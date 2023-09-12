<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use PhpParser\Node\Expr;

/**
 * Transformer dictionary decorator.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final readonly class DictionaryTransformer extends AbstractArrayTransformer
{
    protected function getAssignExpr(Expr $valuesVar, Expr $outputVar, Expr $loopKeyVar, bool $assignByRef): Expr
    {
        if ($assignByRef) {
            return new Expr\AssignRef(new Expr\ArrayDimFetch($valuesVar, $loopKeyVar), $outputVar);
        }

        return new Expr\Assign(new Expr\ArrayDimFetch($valuesVar, $loopKeyVar), $outputVar);
    }
}
