<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\AttributeReference\AttributeInstance;
use AutoMapper\AttributeReference\Reference;
use AutoMapper\AttributeReference\ReflectionReference;
use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

final readonly class ReferenceTransformer implements TransformerInterface
{
    public function __construct(
        private Reference $reference,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        if ($this->reference->methodName) {
            /** ReflectionReference::fromMethod($className, $methodName) */
            $reflectionReferenceExpr = new Expr\StaticCall(
                new Name\FullyQualified(ReflectionReference::class),
                'fromMethod',
                [
                    new Arg(new Scalar\String_($this->reference->className)),
                    new Arg(new Scalar\String_($this->reference->methodName)),
                ]
            );
        } elseif ($this->reference->propertyName) {
            /** ReflectionReference::fromProperty($className, $propertyName) */
            $reflectionReferenceExpr = new Expr\StaticCall(
                new Name\FullyQualified(ReflectionReference::class),
                'fromProperty',
                [
                    new Arg(new Scalar\String_($this->reference->className)),
                    new Arg(new Scalar\String_($this->reference->propertyName)),
                ]
            );
        } else {
            /** ReflectionReference::fromClass($className) */
            $reflectionReferenceExpr = new Expr\StaticCall(
                new Name\FullyQualified(ReflectionReference::class),
                'fromClass',
                [
                    new Arg(new Scalar\String_($this->reference->className)),
                ]
            );
        }

        /** (AttributeInstance::get($attributeClassName, $index, $reference)->transformer)(...) */
        return [
            new Expr\FuncCall(new Expr\PropertyFetch(new Expr\StaticCall(
                new Name\FullyQualified(AttributeInstance::class),
                'get',
                [
                    new Arg(new Scalar\String_($this->reference->attributeClassName)),
                    new Arg($reflectionReferenceExpr),
                    new Arg(new Scalar\Int_($this->reference->attributeIndex)),
                ]
            ), 'transformer'), [
                new Arg($input),
                new Arg($source),
                new Arg(new Expr\Variable('context')),
            ]), [],
        ];
    }
}
