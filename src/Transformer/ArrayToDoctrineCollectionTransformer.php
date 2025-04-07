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

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        /**
         * $collection = new ArrayCollection();.
         */
        $collectionVar = new Expr\Variable($uniqueVariableScope->getUniqueName('collection'));

        $baseAssign = new Expr\New_(new Name(ArrayCollection::class));

        if ($propertyMapping->target->readAccessor !== null) {
            $isDefined = $propertyMapping->target->readAccessor->getIsDefinedExpression(new Expr\Variable('result'));
            $existingValue = $propertyMapping->target->readAccessor->getExpression(new Expr\Variable('result'));

            if (null !== $isDefined) {
                $existingValue = new Expr\Ternary(
                    $isDefined,
                    $existingValue,
                    $baseAssign
                );
            }

            $baseAssign = new Expr\Ternary(
                new Expr\BinaryOp\Coalesce(
                    new Expr\ArrayDimFetch(new Expr\Variable('context'), new Scalar\String_(MapperContext::DEEP_TARGET_TO_POPULATE)),
                    new Expr\ConstFetch(new Name('false'))
                ),
                $existingValue,
                $baseAssign,
            );
        }

        $statements = [
            new Stmt\Expression(new Expr\Assign($collectionVar, $baseAssign)),
        ];

        $loopValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));
        [$output, $itemStatements] = $this->itemTransformer->transform($loopValueVar, $target, $propertyMapping, $uniqueVariableScope, $source);
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
