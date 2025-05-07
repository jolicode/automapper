<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Metadata\GeneratorMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

final readonly class IdentifierHashGenerator
{
    /**
     * @return list<Stmt>
     */
    public function getStatements(GeneratorMetadata $metadata, bool $fromSource): array
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

        $hashCtxVariable = new Expr\Variable('hashCtx');

        $statements = [
            new Stmt\Expression(new Expr\Assign($hashCtxVariable, new Expr\FuncCall(new Name('hash_init'), [
                new Arg(new Scalar\String_('sha256')),
            ]))),
        ];

        $valueVariable = new Expr\Variable('value');

        // foreach property we check
        foreach ($identifiers as $property) {
            if (null === $property->source->accessor || null === $property->target->readAccessor) {
                continue;
            }

            // check if the source is defined
            if ($fromSource) {
                if ($property->source->checkExists) {
                    $statements[] = new Stmt\If_($property->source->accessor->getIsUndefinedExpression($valueVariable), [
                        'stmts' => [
                            new Stmt\Return_(new Expr\ConstFetch(new Name('null'))),
                        ],
                    ]);
                }

                // add identifier to hash
                $statements[] = new Stmt\Expression(new Expr\FuncCall(new Name('hash_update'), [
                    new Arg($hashCtxVariable),
                    new Arg($property->source->accessor->getExpression($valueVariable)),
                ]));
            } else {
                $statements[] = new Stmt\If_($property->target->readAccessor->getIsUndefinedExpression($valueVariable, true), [
                    'stmts' => [
                        new Stmt\Return_(new Expr\ConstFetch(new Name('null'))),
                    ],
                ]);

                $statements[] = new Stmt\Expression(new Expr\FuncCall(new Name('hash_update'), [
                    new Arg($hashCtxVariable),
                    new Arg($property->target->readAccessor->getExpression($valueVariable, true)),
                ]));
            }
        }

        if (\count($statements) < 2) {
            return [];
        }

        // return hash as string
        $statements[] = new Stmt\Return_(new Expr\FuncCall(new Name('hash_final'), [
            new Arg($hashCtxVariable),
            new Arg(new Scalar\String_('true')),
        ]));

        return $statements;
    }
}
