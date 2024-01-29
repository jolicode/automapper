<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\CustomTransformerExtractor;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Extractor\WriteMutator;
use AutoMapper\Generator\TransformerResolver\TransformerResolverInterface;
use AutoMapper\Transformer\AssignedByReferenceTransformerInterface;
use PhpParser\Node\Stmt;

/**
 * @internal
 */
final readonly class PropertyStatementsGenerator
{
    private PropertyConditionsGenerator $propertyConditionsGenerator;

    public function __construct(
        private CustomTransformerExtractor $customTransformerExtractor,
    ) {
        $this->propertyConditionsGenerator = new PropertyConditionsGenerator();
    }

    /**
     * @return list<Stmt>
     */
    public function generate(PropertyMapping $propertyMapping): array
    {
        $mapperMetadata = $propertyMapping->mapperMetadata;

        if ($propertyMapping->shouldIgnoreProperty($mapperMetadata->shouldMapPrivateProperties())) {
            return [];
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        $fieldValueVariable = $variableRegistry->getFieldValueVariable($propertyMapping);

        $transformer = $propertyMapping->getTransformer();

        if (is_string($transformer)) {
            $output = $this->customTransformerExtractor->extract($transformer, $fieldValueVariable, $variableRegistry->getSourceInput());
            $propStatements = [];
        } else {
            /* Create expression to transform the read value into the wanted written value, depending on the transform it may add new statements to get the correct value */
            [$output, $propStatements] = $transformer->transform(
                $fieldValueVariable,
                $variableRegistry->getResult(),
                $propertyMapping,
                $variableRegistry->getUniqueVariableScope()
            );
        }

        if ($propertyMapping->writeMutator && $propertyMapping->writeMutator->type !== WriteMutator::TYPE_ADDER_AND_REMOVER) {
            /**
             * Create expression to write the transformed value to the target
             * only if not add / remove mutator,
             * as it's already called by the transformer in this case
             */
            $writeExpression = $propertyMapping->writeMutator->getExpression(
                $variableRegistry->getResult(),
                $output,
                $transformer instanceof AssignedByReferenceTransformerInterface
                    ? $transformer->assignByRef()
                    : false
            );

            if (null === $writeExpression) {
                return [];
            }

            $propStatements[] = new Stmt\Expression($writeExpression);
        }

        $condition = $this->propertyConditionsGenerator->generate($propertyMapping);

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
