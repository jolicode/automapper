<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\WriteMutator;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AssignedByReferenceTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformer;
use PhpParser\Node\Stmt;

/**
 * @internal
 */
final readonly class PropertyStatementsGenerator
{
    private PropertyConditionsGenerator $propertyConditionsGenerator;

    public function __construct(
    ) {
        $this->propertyConditionsGenerator = new PropertyConditionsGenerator();
    }

    /**
     * @return list<Stmt>
     */
    public function generate(GeneratorMetadata $metadata, PropertyMetadata $propertyMapping): array
    {
        if ($propertyMapping->shouldIgnoreProperty()) {
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

        /* Create expression to transform the read value into the wanted written value, depending on the transform it may add new statements to get the correct value */
        [$output, $propStatements] = $propertyMapping->transformer->transform(
            $fieldValueExpr,
            $variableRegistry->getResult(),
            $propertyMapping,
            $variableRegistry->getUniqueVariableScope(),
            $variableRegistry->getSourceInput()
        );

        if ($propertyMapping->target->writeMutator && $propertyMapping->target->writeMutator->type !== WriteMutator::TYPE_ADDER_AND_REMOVER) {
            /** Create expression to write the transformed value to the target only if not add / remove mutator, as it's already called by the transformer in this case */
            $writeExpression = $propertyMapping->target->writeMutator->getExpression(
                $variableRegistry->getResult(),
                $output,
                $propertyMapping->transformer instanceof AssignedByReferenceTransformerInterface
                    ? $propertyMapping->transformer->assignByRef()
                    : false
            );
            if (null === $writeExpression) {
                return [];
            }

            $propStatements[] = new Stmt\Expression($writeExpression);
        }

        $condition = $this->propertyConditionsGenerator->generate($metadata, $propertyMapping);

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
