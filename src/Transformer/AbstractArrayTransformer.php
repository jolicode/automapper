<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Extractor\WriteMutator;
use AutoMapper\Generator\UniqueVariableScope;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
abstract readonly class AbstractArrayTransformer implements TransformerInterface, DependentTransformerInterface
{
    public function __construct(
        private TransformerInterface $itemTransformer,
    ) {
    }

    abstract protected function getAssignExpr(Expr $valuesVar, Expr $outputVar, Expr $loopKeyVar, bool $assignByRef): Expr;

    public function transform(Expr $input, Expr $target, PropertyMapping $propertyMapping, UniqueVariableScope $uniqueVariableScope, /* Expr\Variable $source */): array
    {
        if (\func_num_args() < 5) {
            trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will have a new "Expr\Variable $source" argument in version 9.0, not defining it is deprecated.', __METHOD__);

            $source = new Expr\Variable('value');
        } else {
            /** @var Expr\Variable $source */
            $source = func_get_arg(4);
        }

        /**
         * $values = [];.
         */
        $valuesVar = new Expr\Variable($uniqueVariableScope->getUniqueName('values'));
        $statements = [
            new Stmt\Expression(new Expr\Assign($valuesVar, new Expr\Array_())),
        ];

        $loopValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));
        $loopKeyVar = new Expr\Variable($uniqueVariableScope->getUniqueName('key'));

        $assignByRef = $this->itemTransformer instanceof AssignedByReferenceTransformerInterface && $this->itemTransformer->assignByRef();

        /* Get the transform statements for the source property */
        [$output, $itemStatements] = $this->itemTransformer->transform($loopValueVar, $target, $propertyMapping, $uniqueVariableScope, $source);

        if ($propertyMapping->writeMutator && $propertyMapping->writeMutator->type === WriteMutator::TYPE_ADDER_AND_REMOVER) {
            /**
             * Use add and remove methods.
             *
             * $target->add($output);
             */
            $mappedValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('mappedValue'));
            $itemStatements[] = new Stmt\Expression(new Expr\Assign($mappedValueVar, $output));
            $itemStatements[] = new Stmt\If_(new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('null')), $mappedValueVar), [
                'stmts' => [
                    new Stmt\Expression($propertyMapping->writeMutator->getExpression($target, $mappedValueVar, $assignByRef)),
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

        $statements[] = new Stmt\Foreach_($input, $loopValueVar, [
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
