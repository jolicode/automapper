<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

class CallableTransformer implements TransformerInterface
{
    public function __construct(private string $callable)
    {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        $newInput = new Expr\FuncCall(
            new Scalar\String_($this->callable),
            [
                new Arg($input),
            ]
        );

        return [$newInput, []];
    }
}
