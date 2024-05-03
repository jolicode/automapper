<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * This class creates both "constructor arguments" and "create target" statements,
 * because these things are closely linked.
 *
 * @internal
 */
final readonly class DiscriminatorStatementsGenerator
{
    public function __construct(
        private ClassDiscriminatorResolver $classDiscriminatorResolver,
        private bool $fromSource,
    ) {
    }

    /**
     * @return list<Stmt>
     *
     * We return the object created with the correct mapper depending on the variant, this will skip the next mapping phase in this situation
     *
     * ```php
     *  if ('VariantA' === $output) {
     *      return $this->mappers['Discriminator_Mapper_VariantA']->map($source, $context);
     *  }
     * ```
     */
    public function createTargetStatements(GeneratorMetadata $metadata): array
    {
        if (!$this->supports($metadata)) {
            return [];
        }

        $propertyMetadata = $this->classDiscriminatorResolver->getDiscriminatorPropertyMetadata($metadata, $this->fromSource);

        if (!$propertyMetadata) {
            return [];
        }

        $variableRegistry = $metadata->variableRegistry;
        $fieldValueExpr = $propertyMetadata->source->accessor?->getExpression($variableRegistry->getSourceInput());

        if (null === $fieldValueExpr) {
            if (!($propertyMetadata->transformer instanceof AllowNullValueTransformerInterface)) {
                return [];
            }

            $fieldValueExpr = new Expr\ConstFetch(new Name('null'));
        }

        // Generate the code that allows to put the type into the output variable,
        // so we are able to determine which mapper to use
        [$output, $createObjectStatements] = $propertyMetadata->transformer->transform(
            $fieldValueExpr,
            $variableRegistry->getResult(),
            $propertyMetadata,
            $variableRegistry->getUniqueVariableScope(),
            $variableRegistry->getSourceInput()
        );

        foreach ($this->classDiscriminatorResolver->discriminatorMapperNamesIndexedByTypeValue($metadata, $this->fromSource) as $typeValue => $discriminatorMapperName) {
            $createObjectStatements[] = new Stmt\If_(
                new Expr\BinaryOp\Identical(new Scalar\String_($typeValue), $output),
                [
                    'stmts' => [
                        new Stmt\Return_(
                            new Expr\MethodCall(
                                new Expr\ArrayDimFetch(
                                    new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
                                    new Scalar\String_($discriminatorMapperName)
                                ),
                                'map',
                                [
                                    new Arg($variableRegistry->getSourceInput()),
                                    new Arg(new Expr\Variable('context')),
                                ]
                            )
                        ),
                    ],
                ]
            );
        }

        return $createObjectStatements;
    }

    private function supports(GeneratorMetadata $metadata): bool
    {
        if (!$this->classDiscriminatorResolver->hasClassDiscriminator($metadata, $this->fromSource)) {
            return false;
        }

        $propertyMetadata = $this->classDiscriminatorResolver->getDiscriminatorPropertyMetadata($metadata, $this->fromSource);

        return $propertyMetadata && $propertyMetadata->transformer instanceof TransformerInterface;
    }
}
