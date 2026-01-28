<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\AttributeReference\AttributeInstance;
use AutoMapper\AttributeReference\Reference;
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
        private bool $objectMapperTransformer = false,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        $reflectionReferenceExpr = $this->reference->getReferenceExpression();

        if ($this->objectMapperTransformer) {
            $args = [
                new Arg($input),
                new Arg($source),
                new Arg(new Expr\ConstFetch(new Name('null'))),
            ];
        } else {
            $args = [
                new Arg($input),
                new Arg($source),
                new Arg(new Expr\Variable('context')),
            ];
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
            ), $this->objectMapperTransformer ? 'transform' : 'transformer'), $args), [],
        ];
    }
}
