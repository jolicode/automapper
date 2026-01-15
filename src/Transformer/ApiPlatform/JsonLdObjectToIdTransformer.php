<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\ApiPlatform;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\CheckTypeInterface;
use AutoMapper\Transformer\DependentTransformerInterface;
use AutoMapper\Transformer\IdentifierHashInterface;
use AutoMapper\Transformer\ObjectTransformer;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformer;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

use function AutoMapper\PhpParser\create_expr_array_item;

final readonly class JsonLdObjectToIdTransformer implements TransformerInterface, DependentTransformerInterface, CheckTypeInterface, IdentifierHashInterface
{
    /**
     * @param array<string> $groups a list of serialization groups to consider when transforming the object to its ID
     */
    public function __construct(
        private ObjectTransformer $objectTransformer,
        private PropertyTransformer $propertyTransformer,
        private array $groups = [],
    ) {
    }

    public function getCheckExpression(
        Expr $input,
        Expr $target,
        PropertyMetadata $propertyMapping,
        UniqueVariableScope $uniqueVariableScope,
        Expr $source,
    ): ?Expr {
        return $this->objectTransformer->getCheckExpression($input, $target, $propertyMapping, $uniqueVariableScope, $source);
    }

    public function getDependencies(): array
    {
        return $this->objectTransformer->getDependencies();
    }

    public function getSourceHashExpression(Expr $source): Expr
    {
        return $this->objectTransformer->getSourceHashExpression($source);
    }

    public function getTargetHashExpression(Expr $target): Expr
    {
        return $this->objectTransformer->getTargetHashExpression($target);
    }

    public function getIdentifierExpression(Expr $input): Expr
    {
        return $this->objectTransformer->getIdentifierExpression($input);
    }

    public function transform(
        Expr $input,
        Expr $target,
        PropertyMetadata $propertyMapping,
        UniqueVariableScope $uniqueVariableScope,
        Expr $source,
        ?Expr $existingValue = null,
    ): array {
        $resultVariable = new Expr\Variable($uniqueVariableScope->getUniqueName('mappedObjectOrId'));
        $contextVariable = new Expr\Variable('context');

        /*
         * If groups is empty we check that there is no groups in context
         */
        if (!$this->groups) {
            $checkGroupsExpr = new Expr\BinaryOp\BooleanOr(
                new Expr\BooleanNot(
                    new Expr\FuncCall(new Name('array_key_exists'), [
                        new Arg(new Scalar\String_(MapperContext::GROUPS)),
                        new Arg($contextVariable),
                    ])
                ),
                new Expr\BooleanNot(
                    new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::GROUPS))
                )
            );
        } else {
            $checkGroupsExpr = new Expr\BinaryOp\BooleanAnd(
                new Expr\BinaryOp\NotIdentical(
                    new Expr\ConstFetch(new Name('null')),
                    new Expr\BinaryOp\Coalesce(
                        new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::GROUPS)),
                        new Expr\Array_()
                    )
                ),
                new Expr\FuncCall(new Name('array_intersect'), [
                    new Arg(
                        new Expr\BinaryOp\Coalesce(
                            new Expr\ArrayDimFetch($contextVariable, new Scalar\String_(MapperContext::GROUPS)),
                            new Expr\Array_()
                        )
                    ),
                    new Arg(new Expr\Array_(array_map(function (string $group) {
                        return create_expr_array_item(new Scalar\String_($group));
                    }, $this->groups))),
                ])
            );
        }

        [$propertyTransformOutput, $propertyTransformStmts] = $this->propertyTransformer->transform(
            $input,
            $target,
            $propertyMapping,
            $uniqueVariableScope,
            $input,
            $existingValue
        );
        $propertyTransformStmts[] = new Stmt\Expression(new Expr\Assign(
            $resultVariable,
            $propertyTransformOutput
        ));

        [$objectTransformOutput, $objectTransformStmts] = $this->objectTransformer->transform(
            $input,
            $target,
            $propertyMapping,
            $uniqueVariableScope,
            $source,
            $existingValue
        );
        $objectTransformStmts[] = new Stmt\Expression(new Expr\AssignRef(
            $resultVariable,
            $objectTransformOutput
        ));

        $ifStatement = new Stmt\If_(
            $checkGroupsExpr,
            [
                'stmts' => $objectTransformStmts,
                'else' => new Stmt\Else_($propertyTransformStmts),
            ]
        );

        return [
            $resultVariable,
            [$ifStatement],
        ];
    }
}
