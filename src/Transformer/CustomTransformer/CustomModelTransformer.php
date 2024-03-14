<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

final readonly class CustomModelTransformer implements TransformerInterface
{
    public function __construct(
        private string $customTransformerId,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        /*
         * When using a custom transformer, we need to call the transform method of the custom transformer which has been injected into the mapper.
         *
         * $this->transformers['id']($input)
         */
        return [new Expr\MethodCall(
            new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'transformers'), new Scalar\String_($this->customTransformerId)),
            'transform',
            [
                new Arg($input),
            ]
        ), []];
    }
}
