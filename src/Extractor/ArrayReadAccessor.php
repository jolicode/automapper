<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

final readonly class ArrayReadAccessor implements ReadAccessorInterface
{
    public function __construct(
        public string $property,
        public bool $isArrayAccess = false,
    ) {
    }

    public function getExpression(Expr $input, bool $target = false): Expr
    {
        return new Expr\ArrayDimFetch($input, new Scalar\String_($this->property));
    }

    public function getIsDefinedExpression(Expr\Variable $input, bool $nullable = false, bool $target = false): ?Expr
    {
        if ($this->isArrayAccess) {
            return new Expr\MethodCall($input, 'offsetExists', [new Arg(new Scalar\String_($this->property))]);
        }

        if (!$nullable) {
            return new Expr\Isset_([new Expr\ArrayDimFetch($input, new Scalar\String_($this->property))]);
        }

        return new Expr\FuncCall(new Name('array_key_exists'), [new Arg(new Scalar\String_($this->property)), new Arg($input)]);
    }

    public function getIsNullExpression(Expr\Variable $input, bool $target = false): Expr
    {
        /*
         * Use the array dim fetch to read the value
         *
         * isset($input['property_name']) && null === $input->property_name
         */
        return new Expr\BinaryOp\LogicalAnd(new Expr\BooleanNot(new Expr\Isset_([new Expr\ArrayDimFetch($input, new Scalar\String_($this->property))])), new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), new Expr\ArrayDimFetch($input, new Scalar\String_($this->property))));
    }

    public function getIsUndefinedExpression(Expr\Variable $input, bool $target = false): Expr
    {
        if ($this->isArrayAccess) {
            return new Expr\BooleanNot(new Expr\MethodCall($input, 'offsetExists', [new Arg(new Scalar\String_($this->property))]));
        }

        return new Expr\BooleanNot(new Expr\FuncCall(new Name('array_key_exists'), [new Arg(new Scalar\String_($this->property)), new Arg($input)]));
    }

    public function getExtractCallback(string $className): ?Expr
    {
        return null;
    }

    public function getExtractIsNullCallback(string $className): ?Expr
    {
        return null;
    }

    public function getExtractIsUndefinedCallback(string $className): ?Expr
    {
        return null;
    }
}
