<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * @internal
 */
class CallableTransformer implements TransformerInterface, AllowNullValueTransformerInterface
{
    public function __construct(
        private string $callable,
        private bool $callableIsMethodFromSource = false,
        private bool $callableIsMethodFromTarget = false,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        if ($this->callableIsMethodFromSource) {
            $newInput = new Expr\MethodCall(
                $source,
                $this->callable,
                [
                    new Arg($input),
                ]
            );

            return [$newInput, []];
        }

        if ($this->callableIsMethodFromTarget) {
            $newInput = new Expr\MethodCall(
                new Expr\Variable('result'),
                $this->callable,
                [
                    new Arg($input),
                ]
            );

            return [$newInput, []];
        }

        $newInput = new Expr\FuncCall(
            new Scalar\String_($this->callable),
            [
                new Arg($input),
            ]
        );

        return [$newInput, []];
    }
}
