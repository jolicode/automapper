<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Metadata\GeneratorMetadata;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;

final readonly class IdentifierEqualGenerator
{
    /**
     * @return list<Stmt>
     */
    public function getStatements(GeneratorMetadata $metadata): array
    {
        $identifiers = [];

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            if (!$propertyMetadata->identifier) {
                continue;
            }

            if (null === $propertyMetadata->target->readAccessor) {
                continue;
            }

            if (null === $propertyMetadata->source->accessor) {
                continue;
            }

            $identifiers[] = $propertyMetadata;
        }

        if (empty($identifiers)) {
            return [];
        }

        $statements = [];

        $sourceVariable = new Expr\Variable('source');
        $targetVariable = new Expr\Variable('target');

        // foreach property we check
        foreach ($identifiers as $property) {
            // check if the source is defined
            if ($property->source->checkExists) {
                $statements[] = new Stmt\If_($property->source->accessor->getIsUndefinedExpression($sourceVariable), [
                    'stmts' => [
                        new Stmt\Return_(new Expr\ConstFetch(new Name('false'))),
                    ],
                ]);
            }

            // check if the target is defined
            $statements[] = new Stmt\If_($property->target->readAccessor->getIsUndefinedExpression($targetVariable, true), [
                'stmts' => [
                    new Stmt\Return_(new Expr\ConstFetch(new Name('false'))),
                ],
            ]);

            // add the identifier check
            $statements[] = new Stmt\If_(
                new Expr\BinaryOp\NotIdentical(
                    $property->source->accessor->getExpression($sourceVariable),
                    $property->target->readAccessor->getExpression($targetVariable, true)
                ),
                [
                    'stmts' => [
                        new Stmt\Return_(new Expr\ConstFetch(new Name('false'))),
                    ],
                ]
            );
        }

        // return true as everything is ok
        $statements[] = new Stmt\Return_(new Expr\ConstFetch(new Name('true')));

        return $statements;
    }
}
