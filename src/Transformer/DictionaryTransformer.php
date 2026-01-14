<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;

use function AutoMapper\PhpParser\create_scalar_int;

/**
 * Transformer dictionary decorator.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final readonly class DictionaryTransformer extends AbstractArrayTransformer implements CheckTypeInterface
{
    /**
     * Assign the value by using the key as the array key.
     *
     * $values[$key] = $output;
     */
    protected function getAssignExpr(Expr $valuesVar, Expr $outputVar, Expr $loopKeyVar, bool $assignByRef): Expr
    {
        if ($assignByRef) {
            return new Expr\AssignRef(new Expr\ArrayDimFetch($valuesVar, $loopKeyVar), $outputVar);
        }

        return new Expr\Assign(new Expr\ArrayDimFetch($valuesVar, $loopKeyVar), $outputVar);
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): ?Expr
    {
        $isArrayCheck = new Expr\FuncCall(
            new Name('is_iterable'),
            [
                new Arg($input),
            ]
        );

        if ($this->itemTransformer instanceof CheckTypeInterface) {
            $itemCheck = $this->itemTransformer->getCheckExpression(new Expr\FuncCall(new Name('current'), [new Arg($input)]), $target, $propertyMapping, $uniqueVariableScope, $source);

            if ($itemCheck) {
                return new Expr\BinaryOp\BooleanAnd($isArrayCheck, new Expr\BinaryOp\BooleanOr(
                    new Expr\BinaryOp\Identical(create_scalar_int(0), new Expr\FuncCall(new Name('count'), [new Arg($input)])),
                    $itemCheck
                ));
            }
        }

        return $isArrayCheck;
    }
}
