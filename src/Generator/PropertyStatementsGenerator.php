<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use AutoMapper\Transformer\AssignedByReferenceTransformerInterface;
use AutoMapper\Transformer\NullableTransformer;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;

/**
 * @internal
 */
final readonly class PropertyStatementsGenerator
{
    public function __construct(
        private PropertyConditionsGenerator $propertyConditionsGenerator,
    ) {
    }

    /**
     * @return Stmt[]
     */
    public function generate(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): array
    {
        if ($propertyMetadata->ignored) {
            return [];
        }

        $variableRegistry = $metadata->variableRegistry;
        $fieldValueExpr = $propertyMetadata->source->accessor?->getExpression($variableRegistry->getSourceInput());

        if (null === $fieldValueExpr) {
            if (!$propertyMetadata->transformer instanceof AllowNullValueTransformerInterface) {
                return [];
            }

            $fieldValueExpr = new Expr\ConstFetch(new Name('null'));
        }

        /* Create expression to transform the read value into the wanted written value, depending on the transform it may add new statements to get the correct value */
        [$output, $propStatements] = $propertyMetadata->transformer->transform(
            $fieldValueExpr,
            $variableRegistry->getResult(),
            $propertyMetadata,
            $variableRegistry->getUniqueVariableScope(),
            $variableRegistry->getSourceInput()
        );

        if ($propertyMetadata->target->writeMutator && !$propertyMetadata->target->writeMutator->isAdderRemover()) {
            /** Create expression to write the transformed value to the target only if not add / remove mutator, as it's already called by the transformer in this case */
            $writeExpression = $propertyMetadata->target->writeMutator->getExpression(
                $variableRegistry->getResult(),
                $output,
                $propertyMetadata->transformer instanceof AssignedByReferenceTransformerInterface
                    ? $propertyMetadata->transformer->assignByRef()
                    : false
            );

            $propStatements[] = new Stmt\Expression($writeExpression);
        }

        if ($propertyMetadata->transformer instanceof NullableTransformer && !$propertyMetadata->transformer->isTargetNullable) {
            $guard = $propertyMetadata->source->checkExists
                ? new Expr\Isset_([$fieldValueExpr])
                : new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('null')), $fieldValueExpr);

            $elseStatements = [];

            if ($propertyMetadata->target->writeMutator && !$propertyMetadata->target->writeMutator->isAdderRemover()) {
                $elseStatements[] = new Stmt\Expression($propertyMetadata->target->writeMutator->getExpression(
                    $variableRegistry->getResult(),
                    new Expr\ConstFetch(new Name('null')),
                    false
                ));
            }

            $propStatements = [
                new Stmt\If_($guard, [
                    'stmts' => $propStatements,
                    'else' => $elseStatements ? new Stmt\Else_($elseStatements) : null,
                ]),
            ];
        }

        $condition = $this->propertyConditionsGenerator->generate($metadata, $propertyMetadata);

        if ($condition) {
            $propStatements = [
                new Stmt\If_($condition, [
                    'stmts' => $propStatements,
                ]),
            ];
        }

        return $propStatements;
    }
}
