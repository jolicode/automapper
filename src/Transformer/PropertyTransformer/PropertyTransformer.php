<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * @internal
 */
final class PropertyTransformer implements TransformerInterface, AllowNullValueTransformerInterface
{
    public function __construct(
        private readonly string $propertyTransformerId,
        private ?Expr $computedValueExpr = null,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        $context = new Expr\Variable('context');
        $computeValueExpr = $this->computedValueExpr ?? new Expr\ConstFetch(new Name('null'));

        $statements = [];
        $transformExpr = new Expr\MethodCall(
            new Expr\MethodCall(new Expr\PropertyFetch(new Expr\Variable('this'), 'serviceLocator'), 'get', [
                new Arg(new Scalar\String_($this->propertyTransformerId)),
            ]),
            'transform',
            [
                new Arg($input),
                new Arg($source),
                new Arg($context),
                new Arg($computeValueExpr),
            ]
        );

        /*
         * If mutator is type adder and remover, we need to loop over the transformed values and call the adder method for each value.
         *
         * $values = $this->transformers['id']($input, $source, $context);
         * foreach ($values as $value) {
         *     $target->add($value);
         * }
         */
        if ($propertyMapping->target->writeMutator?->isAdderRemover()) {
            $mappedValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('mappedValue'));

            $statements[] = new Stmt\Expression(new Expr\Assign(
                $mappedValueVar,
                $transformExpr
            ));

            $loopValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));

            $statements[] = new Stmt\Foreach_($mappedValueVar, $loopValueVar, [
                'stmts' => [
                    new Stmt\Expression($propertyMapping->target->writeMutator->getExpression($target, $loopValueVar)),
                ],
            ]);

            return [new Expr\Variable($uniqueVariableScope->getUniqueName('mappedValues')), $statements];
        }

        /*
         * When using a custom transformer, we need to call the transform method of the custom transformer which has been injected into the mapper.
         *
         * $this->transformers['id']($input, $source, $context)
         */
        return [$transformExpr, []];
    }
}
