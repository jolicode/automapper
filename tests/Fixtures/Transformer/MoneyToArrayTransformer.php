<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;

/**
 * Transform a Money\Money object to an array.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class MoneyToArrayTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        $moneyVar = new Expr\Variable($uniqueVariableScope->getUniqueName('money'));

        return [$moneyVar, [
            new Expression(new Expr\Assign(new Expr\ArrayDimFetch($moneyVar, new String_('amount')), new Expr\MethodCall($input, 'getAmount'))),
            new Expression(new Expr\Assign(new Expr\ArrayDimFetch($moneyVar, new String_('currency')), new Expr\MethodCall(new Expr\MethodCall($input, 'getCurrency'), 'getCode'))),
        ]];
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function assignByRef(): bool
    {
        return false;
    }
}
