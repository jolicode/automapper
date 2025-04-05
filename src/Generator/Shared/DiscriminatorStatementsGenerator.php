<?php

declare(strict_types=1);

namespace AutoMapper\Generator\Shared;

use AutoMapper\Exception\CannotCreateTargetException;
use AutoMapper\Metadata\GeneratorMetadata;
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
            if (!$this->fromSource) {
                return [];
            }

            $createObjectStatements = [];

            // This means we cannot get type from the source, so we get it from the classname
            foreach ($this->classDiscriminatorResolver->discriminatorMapperNames($metadata, $this->fromSource) as $className => $discriminatorMapperName) {
                $createObjectStatements[] = new Stmt\If_(new Expr\Instanceof_(new Expr\Variable('value'), new Name($className)), [
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
                ]);
            }

            return $createObjectStatements;
        }

        // Generate the code that allows to put the type into the output variable,
        // so we are able to determine which mapper to use
        [$output, $discriminateStatements] = $propertyMetadata->transformer->transform(
            $fieldValueExpr,
            $variableRegistry->getResult(),
            $propertyMetadata,
            $variableRegistry->getUniqueVariableScope(),
            $variableRegistry->getSourceInput()
        );

        foreach ($this->classDiscriminatorResolver->discriminatorMapperNamesIndexedByTypeValue($metadata, $this->fromSource) as $typeValue => $discriminatorMapperName) {
            $discriminateStatements[] = new Stmt\If_(
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

        $isDefinedExpression = $propertyMetadata->source->accessor?->getIsDefinedExpression($variableRegistry->getSourceInput());

        if (!$isDefinedExpression) {
            return $discriminateStatements;
        }
        $cannotCreateTarget = ($metadata->mapperMetadata->targetReflectionClass?->isAbstract()
            || $metadata->mapperMetadata->targetReflectionClass?->isInterface()) ?? false;

        $if = new Stmt\If_($isDefinedExpression, [
            'stmts' => $discriminateStatements,
        ]);

        $statements = [$if];

        if ($cannotCreateTarget) {
            $statements[] = new Stmt\Expression(new Expr\Throw_(new Expr\New_(new Name(CannotCreateTargetException::class), [
                new Arg(new Scalar\String_('Cannot create target object, because the target is abstract or an interface, and the property "' . $propertyMetadata->source->property . '" is not defined, or the value does not match any discriminator type.')),
            ])));
        }

        return $statements;
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
