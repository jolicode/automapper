<?php

declare(strict_types=1);

namespace AutoMapper\AttributeReference;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

class Reference
{
    public function __construct(
        public string $attributeClassName,
        public int $attributeIndex,
        public string $className,
        public ?string $propertyName = null,
        public ?string $methodName = null,
    ) {
    }

    public function getReferenceExpression(): Expr
    {
        if ($this->methodName) {
            /** ReflectionReference::fromMethod($className, $methodName) */
            return new Expr\StaticCall(
                new Name\FullyQualified(ReflectionReference::class),
                'fromMethod',
                [
                    new Arg(new Scalar\String_($this->className)),
                    new Arg(new Scalar\String_($this->methodName)),
                ]
            );
        }

        if ($this->propertyName) {
            /** ReflectionReference::fromProperty($className, $propertyName) */
            return new Expr\StaticCall(
                new Name\FullyQualified(ReflectionReference::class),
                'fromProperty',
                [
                    new Arg(new Scalar\String_($this->className)),
                    new Arg(new Scalar\String_($this->propertyName)),
                ]
            );
        }

        /** ReflectionReference::fromClass($className) */
        return new Expr\StaticCall(
            new Name\FullyQualified(ReflectionReference::class),
            'fromClass',
            [
                new Arg(new Scalar\String_($this->className)),
            ]
        );
    }
}
