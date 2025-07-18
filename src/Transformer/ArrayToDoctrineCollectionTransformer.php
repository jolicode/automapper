<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\PropertyMetadata;
use Doctrine\Common\Collections\ArrayCollection;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

/**
 * Transform an array to Money\Money object.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class ArrayToDoctrineCollectionTransformer implements TransformerInterface, DependentTransformerInterface, PrioritizedTransformerFactoryInterface
{
    public function __construct(
        protected TransformerInterface $itemTransformer,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source, ?Expr $existingValue = null): array
    {
        /**
         * $collection = new ArrayCollection();.
         */
        $collectionVar = new Expr\Variable($uniqueVariableScope->getUniqueName('collection'));
        $exisingValuesIndexed = new Expr\Variable($uniqueVariableScope->getUniqueName('existingValuesIndexed'));

        $statements = [
            new Stmt\Expression(new Expr\Assign($collectionVar, new Expr\New_(new Name(ArrayCollection::class)))),
            new Stmt\Expression(new Expr\Assign($exisingValuesIndexed, new Expr\Array_())),
        ];

        $loopValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));

        $existingValue = new Expr\Variable($uniqueVariableScope->getUniqueName('existingValue'));
        $loopExistingValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('existingValue'));

        [$output, $itemStatements] = $this->itemTransformer->transform($loopValueVar, $target, $propertyMapping, $uniqueVariableScope, $source, $existingValue);

        if ($propertyMapping->target->readAccessor !== null && $this->itemTransformer instanceof IdentifierHashInterface) {
            $hashValueVariable = new Expr\Variable($uniqueVariableScope->getUniqueName('hashValue'));
            $statements[] = new Stmt\If_(new Expr\BinaryOp\Coalesce(
                new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DEEP_TARGET_TO_POPULATE)),
                new Expr\ConstFetch(new Name('false'))
            ), [
                'stmts' => [
                    new Stmt\Foreach_($propertyMapping->target->readAccessor->getExpression($target), $loopExistingValueVar, [
                        'stmts' => [
                            new Stmt\Expression(new Expr\Assign($hashValueVariable, $this->itemTransformer->getTargetHashExpression($loopExistingValueVar))),
                            new Stmt\If_(new Expr\BinaryOp\NotIdentical(new Expr\ConstFetch(new Name('null')), $hashValueVariable), [
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

        $itemStatements[] = new Stmt\Expression(new Expr\MethodCall($collectionVar, 'add', [new Arg($output)]));

        $statements[] = new Stmt\Foreach_(new Expr\BinaryOp\Coalesce($input, new Expr\Array_()), $loopValueVar, [
            'stmts' => $itemStatements,
        ]);

        return [$collectionVar, $statements];
    }

    public function getDependencies(): array
    {
        if (!$this->itemTransformer instanceof DependentTransformerInterface) {
            return [];
        }

        return $this->itemTransformer->getDependencies();
    }

    public function getPriority(): int
    {
        return 0;
    }
}
