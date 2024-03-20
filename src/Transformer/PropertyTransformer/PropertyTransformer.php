<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * @internal
 */
final readonly class PropertyTransformer implements TransformerInterface
{
    public function __construct(
        private string $propertyTransformerId,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        /*
         * When using a custom transformer, we need to call the transform method of the custom transformer which has been injected into the mapper.
         *
         * $this->transformers['id']($input, $source, $context)
         */
        return [new Expr\MethodCall(
            new Expr\ArrayDimFetch(new Expr\PropertyFetch(new Expr\Variable('this'), 'transformers'), new Scalar\String_($this->propertyTransformerId)),
            'transform',
            [
                new Arg($input),
                new Arg($source),
                new Arg(new Expr\Variable('context')),
            ]
        ), []];
    }
}
