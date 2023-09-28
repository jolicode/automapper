<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * Handle custom callback transformation.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class CallbackTransformer implements TransformerInterface
{
    public function __construct(
        private readonly string $callbackName,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope): array
    {
        $arguments = [
            new Arg($input),
            new Arg($target),
        ];

        /*
         * $this->callbacks['callbackName']($input, $target);
         */
        return [new Expr\FuncCall(
            new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'callbacks'), new Scalar\String_($this->callbackName)), $arguments),
            [],
        ];
    }
}
