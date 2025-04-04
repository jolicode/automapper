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
 *
 * @internal
 */
abstract readonly class AbstractArrayTransformer implements TransformerInterface, DependentTransformerInterface
{
    public function __construct(
        protected TransformerInterface $itemTransformer,
    ) {
    }

    abstract protected function getAssignExpr(Expr $valuesVar, Expr $outputVar, Expr $loopKeyVar, bool $assignByRef): Expr;

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        /**
         * $values = [];.
         */
        $valuesVar = new Expr\Variable($uniqueVariableScope->getUniqueName('values'));
        $baseAssign = new Expr\Array_();

        if ($propertyMapping->target->readAccessor !== null) {
            $baseAssign = new Expr\Ternary(
                new Expr\BinaryOp\Coalesce(
                    new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DEEP_TARGET_TO_POPULATE)),
                    new Expr\ConstFetch(new Name('false'))
                ),
                $propertyMapping->target->readAccessor->getExpression(new Expr\Variable('result')),
                new Expr\Array_()
            );
        }

        $statements = [
            new Stmt\Expression(new Expr\Assign($valuesVar, $baseAssign)),
        ];

        $loopValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));
        $loopKeyVar = new Expr\Variable($uniqueVariableScope->getUniqueName('key'));

        $assignByRef = $this->itemTransformer instanceof AssignedByReferenceTransformerInterface && $this->itemTransformer->assignByRef();

        /* Get the transform statements for the source property */
        [$output, $itemStatements] = $this->itemTransformer->transform($loopValueVar, $target, $propertyMapping, $uniqueVariableScope, $source);

        if (null === $propertyMapping->target->parameterInConstructor && $propertyMapping->target->writeMutator && $propertyMapping->target->writeMutator->type === WriteMutator::TYPE_ADDER_AND_REMOVER) {
            /**
             * Use add and remove methods.
             *
             * $target->add($output);
             */
            $loopRemoveValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('removeValue'));
            $removeExpr = $propertyMapping->target->writeMutator->getRemoveExpression($target, $loopRemoveValueVar);

            if ($propertyMapping->target->readAccessor !== null && $removeExpr !== null) {
                $statements[] = new Stmt\Foreach_($propertyMapping->target->readAccessor->getExpression($target), $loopRemoveValueVar, [
                    'stmts' => [
                        new Stmt\Expression($removeExpr),
                    ],
                ]);
            }

            $mappedValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('mappedValue'));
            $itemStatements[] = new Stmt\Expression(new Expr\Assign($mappedValueVar, $output));
            $itemStatements[] = new Stmt\If_(new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('null')), $mappedValueVar), [
                'stmts' => [
                    new Stmt\Expression($propertyMapping->target->writeMutator->getExpression($target, $mappedValueVar, $assignByRef)),
                ],
            ]);
        } else {
            /*
             * Assign the value to the array.
             *
             * $values[] = $output;
             * or
             * $values[$key] = $output;
             */
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
}
