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

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source, ?Expr $existingValue = null): array
    {
        if ($this->callableIsMethodFromSource || $this->callableIsMethodFromTarget) {
            return [new Expr\MethodCall(
                $this->callableIsMethodFromSource ? $source : new Expr\Variable('result'),
                $this->callable,
                [new Arg($input), new Arg($source), new Arg(new Expr\Variable('context'))],
            ), []];
        }

        return [new Expr\FuncCall(
            new Scalar\String_($this->callable),
            // Internal functions throws ArgumentCountError when too many arguments are passed
            \function_exists($this->callable) && (new \ReflectionFunction($this->callable))->isInternal()
                ? [new Arg($input)]
                : [new Arg($input), new Arg($source), new Arg(new Expr\Variable('context'))]
        ), []];
    }
}
