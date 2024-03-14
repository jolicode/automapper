<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\TransformerInterface;
use Money\Currency;
use Money\Money;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;

/**
 * Transform an array to Money\Money object.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class ArrayToMoneyTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        return [new Expr\New_(new Name\FullyQualified(Money::class), [
            new Arg(new Expr\ArrayDimFetch($input, new String_('amount'))),
            new Arg(new Expr\New_(new Name\FullyQualified(Currency::class), [
                new Arg(new Expr\ArrayDimFetch($input, new String_('currency'))),
            ])),
        ]), []];
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
