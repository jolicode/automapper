<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\WriteMutator;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AssignedByReferenceTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformer;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal
 */
final readonly class PropertyStatementsGenerator
{
    private PropertyConditionsGenerator $propertyConditionsGenerator;

    public function __construct(
        ExpressionLanguage $expressionLanguage = new ExpressionLanguage()
    ) {
        $this->propertyConditionsGenerator = new PropertyConditionsGenerator($expressionLanguage);
    }

    /**
     * @return list<Stmt>
     */
    public function generate(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): array
    {
        if ($propertyMetadata->shouldIgnoreProperty()) {
            return [];
        }

        $variableRegistry = $metadata->variableRegistry;
        $fieldValueExpr = $propertyMetadata->source->accessor?->getExpression($variableRegistry->getSourceInput());

        if (null === $fieldValueExpr) {
            if (!($propertyMetadata->transformer instanceof PropertyTransformer)) {
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

        if ($propertyMetadata->target->writeMutator && $propertyMetadata->target->writeMutator->type !== WriteMutator::TYPE_ADDER_AND_REMOVER) {
            /** Create expression to write the transformed value to the target only if not add / remove mutator, as it's already called by the transformer in this case */
            $writeExpression = $propertyMetadata->target->writeMutator->getExpression(
                $variableRegistry->getResult(),
                $output,
                $propertyMetadata->transformer instanceof AssignedByReferenceTransformerInterface
                    ? $propertyMetadata->transformer->assignByRef()
                    : false
            );
            if (null === $writeExpression) {
                return [];
            }

            $propStatements[] = new Stmt\Expression($writeExpression);
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
