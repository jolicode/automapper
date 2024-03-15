<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformer;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
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
    ) {
    }

    /**
     * @return list<Stmt>
     */
    public function injectMapperStatements(GeneratorMetadata $metadata): array
    {
        if (!$this->supports($metadata)) {
            return [];
        }

        $discriminatorMapperNames = $this->classDiscriminatorResolver->discriminatorMapperNames($metadata);

        $injectMapperStatements = [];

        foreach ($discriminatorMapperNames as $typeTarget => $discriminatorMapperName) {
            /*
             * We inject dependencies for all the discriminator variant
             *
             * ```php
             *  $this->mappers['Discriminator_Mapper_VariantA'] = $autoMapperRegistry->getMapper($source, VariantA::class);
             *  $this->mappers['Discriminator_Mapper_VariantB'] = $autoMapperRegistry->getMapper($source, VariantB::class);
             *  ...
             * ```
             */
            $injectMapperStatements[] = new Stmt\Expression(
                new Expr\Assign(
                    new Expr\ArrayDimFetch(
                        new Expr\PropertyFetch(new Expr\Variable('this'), 'mappers'),
                        new Scalar\String_($discriminatorMapperName)
                    ),
                    new Expr\MethodCall(new Expr\Variable('autoMapperRegistry'), 'getMapper', [
                        new Arg(new Scalar\String_($metadata->mapperMetadata->source)),
                        new Arg(new Scalar\String_($typeTarget)),
                    ])
                )
            );
        }

        return $injectMapperStatements;
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

        $propertyMapping = $this->classDiscriminatorResolver->propertyMapping($metadata);

        if (!$propertyMapping || $propertyMapping->transformer === null) {
            return [];
        }

        $variableRegistry = $metadata->variableRegistry;
        $fieldValueExpr = $propertyMapping->source->accessor?->getExpression($variableRegistry->getSourceInput());
        if (null === $fieldValueExpr) {
            if (!($propertyMapping->transformer instanceof CustomPropertyTransformer)) {
                return [];
            }

            $fieldValueExpr = $variableRegistry->getSourceInput();
        }

        // Generate the code that allows to put the type into the output variable,
        // so we are able to determine which mapper to use
        [$output, $createObjectStatements] = $propertyMapping->transformer->transform(
            $fieldValueExpr,
            $variableRegistry->getResult(),
            $propertyMapping,
            $variableRegistry->getUniqueVariableScope(),
            $variableRegistry->getSourceInput()
        );

        foreach ($this->classDiscriminatorResolver->discriminatorMapperNamesIndexedByTypeValue($metadata) as $typeValue => $discriminatorMapperName) {
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
        if (!$this->classDiscriminatorResolver->hasClassDiscriminator($metadata)) {
            return false;
        }

        $propertyMapping = $this->classDiscriminatorResolver->propertyMapping($metadata);

        return $propertyMapping && $propertyMapping->transformer instanceof TransformerInterface;
    }
}
