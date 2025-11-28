<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\WriteMutator;
use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *M
 *
 * @internal
 */
abstract readonly class AbstractArrayTransformer implements \Stringable, TransformerInterface, DependentTransformerInterface
{
    public function __construct(
        protected TransformerInterface $itemTransformer,
    ) {
    }

    abstract protected function getAssignExpr(Expr $valuesVar, Expr $outputVar, Expr $loopKeyVar, bool $assignByRef): Expr;

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source, ?Expr $existingValue = null): array
    {
        /**
         * $values = [];.
         */
        $valuesVar = new Expr\Variable($uniqueVariableScope->getUniqueName('values'));
        $exisingValuesIndexed = new Expr\Variable($uniqueVariableScope->getUniqueName('existingValuesIndexed'));

        $statements = [
            new Stmt\Expression(new Expr\Assign($valuesVar, new Expr\Array_())),
            new Stmt\Expression(new Expr\Assign($exisingValuesIndexed, new Expr\Array_())),
        ];

        $loopValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));
        $loopKeyVar = new Expr\Variable($uniqueVariableScope->getUniqueName('key'));

        $itemStatements = [];
        $existingValue = new Expr\Variable($uniqueVariableScope->getUniqueName('existingValue'));
        $assignByRef = $this->itemTransformer instanceof AssignedByReferenceTransformerInterface && $this->itemTransformer->assignByRef();

        /* Get the transform statements for the source property */
        [$output, $transformStatements] = $this->itemTransformer->transform($loopValueVar, $target, $propertyMapping, $uniqueVariableScope, $source, $existingValue);

        $itemStatements = array_merge($itemStatements, $transformStatements);

        if (null === $propertyMapping->target->parameterInConstructor && $propertyMapping->target->writeMutator && $propertyMapping->target->writeMutator->type === WriteMutator::TYPE_ADDER_AND_REMOVER) {
            /**
             * Use add and remove methods.
             *
             * $target->add($output);
             */
            $loopRemoveValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('removeValue'));
            $removeExpr = $propertyMapping->target->writeMutator->getRemoveExpression($target, $loopRemoveValueVar);

            if ($propertyMapping->target->readAccessor !== null && $removeExpr !== null) {
                $loopExistingStatements = [];
                $isDeepPopulateExpr = new Expr\BinaryOp\Coalesce(
                    new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DEEP_TARGET_TO_POPULATE)),
                    new Expr\ConstFetch(new Name('false'))
                );

                if ($propertyMapping->target->readAccessor !== null && $this->itemTransformer instanceof IdentifierHashInterface) {
                    $targetHashVar = new Expr\Variable($uniqueVariableScope->getUniqueName('targetHash'));

                    $loopExistingStatements[] = new Stmt\If_($isDeepPopulateExpr, [
                        'stmts' => [
                            new Stmt\Expression(new Expr\Assign($targetHashVar, $this->itemTransformer->getTargetHashExpression($loopRemoveValueVar))),
                            new Stmt\If_(new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name("''")), $targetHashVar), [
                                'stmts' => [new Stmt\Expression(new Expr\Assign(new Expr\ArrayDimFetch($exisingValuesIndexed, $targetHashVar), $loopRemoveValueVar))],
                            ]),
                        ],
                    ]);
                }

                $loopExistingStatements[] = new Stmt\Expression($removeExpr);

                $statements[] = new Stmt\Foreach_($propertyMapping->target->readAccessor->getExpression($target), $loopRemoveValueVar, [
                    'stmts' => $loopExistingStatements,
                ]);
            }

            $mappedValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('mappedValue'));
            $hashValueTargetVariable = new Expr\Variable($uniqueVariableScope->getUniqueName('hashValueTarget'));

            if ($propertyMapping->target->readAccessor !== null && $this->itemTransformer instanceof IdentifierHashInterface) {
                $itemStatements[] = new Stmt\Expression(new Expr\Assign($hashValueTargetVariable, $this->itemTransformer->getSourceHashExpression($loopValueVar)));
                $itemStatements[] = new Stmt\Expression(new Expr\Assign($existingValue, new Expr\BinaryOp\Coalesce(new Expr\ArrayDimFetch($exisingValuesIndexed, $hashValueTargetVariable), new Expr\ConstFetch(new Name('null')))));
            }
            $itemStatements[] = new Stmt\Expression(new Expr\Assign($mappedValueVar, $output));
            $itemStatements[] = new Stmt\If_(new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('null')), $mappedValueVar), [
                'stmts' => [
                    new Stmt\Expression($propertyMapping->target->writeMutator->getExpression($target, $mappedValueVar, $assignByRef)),
                ],
            ]);
        } else {
            $loopExistingValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('existingValue'));

            if ($propertyMapping->target->readAccessor !== null && $this->itemTransformer instanceof IdentifierHashInterface) {
                $hashValueVariable = new Expr\Variable($uniqueVariableScope->getUniqueName('hashValue'));

                $isDeepPopulateExpr = new Expr\BinaryOp\Coalesce(
                    new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DEEP_TARGET_TO_POPULATE)),
                    new Expr\ConstFetch(new Name('false'))
                );

                $isDefinedExpr = $propertyMapping->target->readAccessor->getIsDefinedExpression(new Expr\Variable('result'));

                if ($isDefinedExpr !== null) {
                    $isDeepPopulateExpr = new Expr\BinaryOp\BooleanAnd($isDeepPopulateExpr, $isDefinedExpr);
                }

                $statements[] = new Stmt\If_($isDeepPopulateExpr, [
                    'stmts' => [
                        new Stmt\Foreach_($propertyMapping->target->readAccessor->getExpression($target), $loopExistingValueVar, [
                            'stmts' => [
                                new Stmt\Expression(new Expr\Assign($hashValueVariable, $this->itemTransformer->getTargetHashExpression($loopExistingValueVar))),
                                new Stmt\If_(new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name("''")), $hashValueVariable), [
                                    'stmts' => [
                                        new Stmt\Expression(new Expr\Assign(new Expr\ArrayDimFetch($exisingValuesIndexed, $hashValueVariable), $loopExistingValueVar)),
                                    ],
                                ]),
                            ],
                        ]),
                    ],
                ]);

                $hashValueTargetVariable = new Expr\Variable($uniqueVariableScope->getUniqueName('hashValueTarget'));
                $itemStatements[] = new Stmt\Expression(new Expr\Assign($hashValueTargetVariable, $this->itemTransformer->getSourceHashExpression($loopValueVar)));
                $itemStatements[] = new Stmt\Expression(new Expr\Assign($existingValue, new Expr\BinaryOp\Coalesce(new Expr\ArrayDimFetch($exisingValuesIndexed, $hashValueTargetVariable), new Expr\ConstFetch(new Name('null')))));
            }

            $itemStatements[] = new Stmt\Expression($this->getAssignExpr($valuesVar, $output, $loopKeyVar, $assignByRef));
        }

        $statements[] = new Stmt\Foreach_(new Expr\BinaryOp\Coalesce($input, new Expr\Array_()), $loopValueVar, [
            'stmts' => $itemStatements,
            'keyVar' => $loopKeyVar,
        ]);

        return [$valuesVar, $statements];
    }

    public function getDependencies(): array
    {
        if (!$this->itemTransformer instanceof DependentTransformerInterface) {
            return [];
        }

        return $this->itemTransformer->getDependencies();
    }

    public function __toString(): string
    {
        return \sprintf('%s<%s>', static::class, $this->itemTransformer instanceof \Stringable ? (string) $this->itemTransformer : \get_class($this->itemTransformer));
    }
}
