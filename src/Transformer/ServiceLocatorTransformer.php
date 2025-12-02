<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * Use a service locator to transform.
 *
 * @internal
 */
final class ServiceLocatorTransformer implements TransformerInterface
{
    public function __construct(
        private string $serviceId,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        /** $this->serviceLocator?->get($serviceId)->__invoke($value, $source, $context) */
        return [
            new Expr\MethodCall(
                new Expr\MethodCall(
                    new Expr\NullsafePropertyFetch(
                        new Expr\Variable('this'),
                        'serviceLocator'
                    ),
                    'get',
                    [
                        new Arg(new Scalar\String_($this->serviceId)),
                    ]
                ),
                '__invoke',
                [
                    new Arg($input),
                    new Arg($source),
                    new Arg(new Expr\Variable('result')),
                ]
            ),
            [],
        ];
    }
}
