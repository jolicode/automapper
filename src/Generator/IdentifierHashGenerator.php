<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\ReadAccessor;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Transformer\IdentifierHashInterface;
use PhpParser\Builder;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

final readonly class IdentifierHashGenerator
{
    /**
     * @return list<Stmt>
     */
    private function getStatements(GeneratorMetadata $metadata, bool $fromSource): array
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

    /**
     * Create the getSourceHash method for this mapper.
     *
     * ```php
     * public function getSourceHash(mixed $source, mixed $target): ?string {
     *    ... // statements
     * }
     * ```
     */
    public function getSourceHashMethod(GeneratorMetadata $metadata): ?Stmt\ClassMethod
    {
        $stmts = $this->getStatements($metadata, true);

        if (empty($stmts)) {
            return null;
        }

        return (new Builder\Method('getSourceHash'))
            ->makePublic()
            ->setReturnType('?string')
            ->addParam(new Param(
                var: new Expr\Variable('value'),
                type: new Name('mixed'))
            )
            ->addStmts($stmts)
            ->getNode();
    }

    /**
     * Create the getTargetHash method for this mapper.
     *
     * ```php
     * public function getTargetHash(mixed $source, mixed $target): ?string {
     *    ... // statements
     * }
     * ```
     */
    public function getTargetHashMethod(GeneratorMetadata $metadata): ?Stmt\ClassMethod
    {
        $stmts = $this->getStatements($metadata, false);

        if (empty($stmts)) {
            return null;
        }

        return (new Builder\Method('getTargetHash'))
            ->makePublic()
            ->setReturnType('?string')
            ->addParam(new Param(
                var: new Expr\Variable('value'),
                type: new Name('mixed'))
            )
            ->addStmts($stmts)
            ->getNode();
    }

    /**
     * Create the getTargetIdentifiers method for this mapper.
     *
     * ```php
     * public function getTargetIdentifiers(mixed $source): mixed {
     *    ... // statements
     * }
     * ```
     */
    public function getTargetIdentifiersMethod(GeneratorMetadata $metadata): ?Stmt\ClassMethod
    {
        $identifiers = [];

        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            if (!$propertyMetadata->identifier) {
                continue;
            }

            if (null === $propertyMetadata->source->accessor) {
                continue;
            }

            $identifiers[] = $propertyMetadata;
        }

        if (empty($identifiers)) {
            return null;
        }

        $isUnique = \count($identifiers) === 1;

        $identifiersVariable = new Expr\Variable('identifiers');
        $valueVariable = new Expr\Variable('value');
        $statements = [];

        if (!$isUnique) {
            $statements[] = new Stmt\Expression(new Expr\Assign($identifiersVariable, new Expr\Array_()));
        }

        // foreach property we check
        foreach ($identifiers as $property) {
            /** @var ReadAccessor $accessor */
            $accessor = $property->source->accessor;

            // check if the source is defined
            if ($property->source->checkExists) {
                $statements[] = new Stmt\If_($accessor->getIsUndefinedExpression($valueVariable), [
                    'stmts' => [
                        new Stmt\Return_(new Expr\ConstFetch(new Name('null'))),
                    ],
                ]);
            }

            $fieldValueExpr = $accessor->getExpression($valueVariable);
            $transformer = $property->transformer;

            if ($transformer instanceof IdentifierHashInterface) {
                $fieldValueExpr = $transformer->getIdentifierExpression($fieldValueExpr);
            }

            if ($isUnique) {
                $statements[] = new Stmt\Return_($fieldValueExpr);
            } else {
                $statements[] = new Stmt\Expression(new Expr\Assign(
                    new Expr\ArrayDimFetch($identifiersVariable, new Scalar\String_($property->target->property)),
                    $fieldValueExpr
                ));
            }
        }

        // return hash as string
        if (!$isUnique) {
            $statements[] = new Stmt\Return_($identifiersVariable);
        }

        return (new Builder\Method('getTargetIdentifiers'))
            ->makePublic()
            ->setReturnType('mixed')
            ->addParam(new Param(
                var: new Expr\Variable('value'),
                type: new Name('mixed'))
            )
            ->addStmts($statements)
            ->getNode();
    }
}
