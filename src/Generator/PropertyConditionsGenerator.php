<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Exception\CompileException;
use AutoMapper\MapperContext;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use MongoDB\BSON\Document;
use MongoDB\Model\BSONDocument;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function AutoMapper\PhpParser\create_expr_array_item;
use function AutoMapper\PhpParser\create_scalar_int;

/**
 * We generate a list of conditions that will allow the field to be mapped to the target.
 *
 * @internal
 */
final readonly class PropertyConditionsGenerator
{
    private Parser $parser;

    public function __construct(
        private ExpressionLanguage $expressionLanguage,
        Parser $parser = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
    }

    public function generate(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Expr
    {
        $conditions = [];

        $conditions[] = $this->propertyExistsForStdClass($metadata, $propertyMetadata);
        $conditions[] = $this->propertyExistsForArray($metadata, $propertyMetadata);
        $conditions[] = $this->propertyExistsForBSONDocument($metadata, $propertyMetadata);
        $conditions[] = $this->isAllowedAttribute($metadata, $propertyMetadata);

        if (!$propertyMetadata->disableGroupsCheck) {
            $conditions[] = $this->groupsCheck($metadata->variableRegistry, $propertyMetadata->groups); // Property groups

            if ($propertyMetadata->groups === null) {
                $conditions[] = $this->groupsCheck($metadata->variableRegistry, $propertyMetadata->source->groups); // Source groups
                $conditions[] = $this->groupsCheck($metadata->variableRegistry, $propertyMetadata->target->groups); // Target groups
            }

            $conditions[] = $this->noGroupsCheck($metadata, $propertyMetadata);
        }

        $conditions[] = $this->maxDepthCheck($metadata, $propertyMetadata);
        $conditions[] = $this->customCondition($metadata, $propertyMetadata);

        $conditions = array_values(array_filter($conditions));

        if (!$conditions) {
            return null;
        }

        /**
         * If there is any conditions generated we encapsulate the mapping into it.
         *
         * ```php
         * if (condition1 && condition2 && ...) {
         *    ... // mapping statements
         * }
         * ```
         */
        $condition = array_shift($conditions);

        while ($conditions) {
            $condition = new Expr\BinaryOp\BooleanAnd($condition, array_shift($conditions));
        }

        return $condition;
    }

    /**
     * In case of source is an \stdClass we ensure that the property exists.
     *
     * ```php
     * property_exists($source, 'propertyName')
     * ```
     */
    private function propertyExistsForStdClass(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Expr
    {
        if (!$propertyMetadata->source->checkExists || \stdClass::class !== $metadata->mapperMetadata->source) {
            return null;
        }

        return new Expr\FuncCall(new Name('property_exists'), [
            new Arg($metadata->variableRegistry->getSourceInput()),
            new Arg(new Scalar\String_($propertyMetadata->source->property)),
        ]);
    }

    /**
     * In case of source is an array we ensure that the key exists.
     *
     * ```php
     * array_key_exists('propertyName', $source).
     * ```
     */
    private function propertyExistsForArray(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Expr
    {
        if (!$propertyMetadata->source->checkExists || 'array' !== $metadata->mapperMetadata->source) {
            return null;
        }

        return new Expr\FuncCall(new Name('array_key_exists'), [
            new Arg(new Scalar\String_($propertyMetadata->source->property)),
            new Arg($metadata->variableRegistry->getSourceInput()),
        ]);
    }

    /**
     * In case of source is an array we ensure that the key exists.
     *
     * ```php
     * $source->offsetExists('propertyName').
     * ```
     */
    private function propertyExistsForBSONDocument(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Expr
    {
        if (!$propertyMetadata->source->checkExists || !in_array($metadata->mapperMetadata->source, [Document::class, BSONDocument::class], true)) {
            return null;
        }

        return new Expr\MethodCall(
            $metadata->variableRegistry->getSourceInput(),
            'offsetExists',
            [new Arg(new Scalar\String_($propertyMetadata->source->property))],
        );
    }

    /**
     * In case of supporting attributes checking, we check if the property is allowed to be mapped.
     *
     * ```php
     * MapperContext::isAllowedAttribute($context, 'propertyName', isset($source->field)).
     * ```
     */
    private function isAllowedAttribute(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Expr
    {
        if (!$propertyMetadata->source->accessor || !$metadata->checkAttributes) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;

        return new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'isAllowedAttribute', [
            new Arg($variableRegistry->getContext()),
            new Arg(new Scalar\String_($propertyMetadata->source->property)),
            new Arg($propertyMetadata->source->accessor->getIsNullExpression($variableRegistry->getSourceInput())),
        ]);
    }

    /**
     * When there is groups associated we check if the context has the same groups.
     *
     * ```php
     * (null !== $context[MapperContext::GROUPS] ?? null && array_intersect($context[MapperContext::GROUPS] ?? [], ['group1', 'group2']))
     * ```
     *
     * @param string[]|null $groups
     */
    private function groupsCheck(VariableRegistry $variableRegistry, ?array $groups = []): ?Expr
    {
        if (!$groups) {
            return null;
        }

        return new Expr\BinaryOp\BooleanAnd(
            new Expr\BinaryOp\NotIdentical(
                new Expr\ConstFetch(new Name('null')),
                new Expr\BinaryOp\Coalesce(
                    new Expr\ArrayDimFetch($variableRegistry->getContext(), new Scalar\String_(MapperContext::GROUPS)),
                    new Expr\Array_()
                )
            ),
            new Expr\FuncCall(new Name('array_intersect'), [
                new Arg(
                    new Expr\BinaryOp\Coalesce(
                        new Expr\ArrayDimFetch($variableRegistry->getContext(), new Scalar\String_(MapperContext::GROUPS)),
                        new Expr\Array_()
                    )
                ),
                new Arg(new Expr\Array_(array_map(function (string $group) { // @phpstan-ignore argument.type
                    return create_expr_array_item(new Scalar\String_($group));
                }, $groups))),
            ])
        );
    }

    /**
     * When there is no groups associated to the target property or source property we check if the context has the same groups.
     *
     * ```php
     * (!array_key_exists(MapperContext::GROUPS, $context) || !$context[MapperContext::GROUPS])
     * ```
     */
    private function noGroupsCheck(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Expr
    {
        if ($propertyMetadata->groups || $propertyMetadata->target->groups || $propertyMetadata->source->groups) {
            return null;
        }

        return new Expr\BinaryOp\BooleanOr(
            new Expr\BooleanNot(
                new Expr\FuncCall(new Name('array_key_exists'), [
                    new Arg(new Scalar\String_(MapperContext::GROUPS)),
                    new Arg($metadata->variableRegistry->getContext()),
                ])
            ),
            new Expr\BooleanNot(
                new Expr\ArrayDimFetch($metadata->variableRegistry->getContext(), new Scalar\String_(MapperContext::GROUPS))
            )
        );
    }

    /**
     * When there is a max depth for this property we check if the context has a depth lower or equal to the max depth.
     *
     * ```php
     * ($context[MapperContext::DEPTH] ?? 0) <= $maxDepth
     * ```
     */
    private function maxDepthCheck(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Expr
    {
        if (!$propertyMetadata->maxDepth) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;

        return new Expr\BinaryOp\SmallerOrEqual(
            new Expr\BinaryOp\Coalesce(
                new Expr\ArrayDimFetch($variableRegistry->getContext(), new Scalar\String_(MapperContext::DEPTH)),
                new Expr\ConstFetch(new Name('0'))
            ),
            create_scalar_int($propertyMetadata->maxDepth),
        );
    }

    /**
     * When there is a if condition we check if the condition is true.
     */
    private function customCondition(GeneratorMetadata $metadata, PropertyMetadata $propertyMetadata): ?Expr
    {
        if (null === $propertyMetadata->if) {
            return null;
        }

        $callableName = null;

        if (\is_callable($propertyMetadata->if, false, $callableName)) {
            if (\function_exists($callableName)) {
                // Get arguments count of the function
                $reflectionFunction = new \ReflectionFunction($callableName);
                $argumentsCount = $reflectionFunction->getNumberOfRequiredParameters();

                if ($argumentsCount === 1) {
                    return new Expr\FuncCall(
                        new Name($callableName),
                        [
                            new Arg(new Expr\Variable('value')),
                        ]
                    );
                }

                if ($argumentsCount > 2) {
                    throw new CompileException('Callable condition must have 1 or 2 arguments required, but it has ' . $argumentsCount);
                }
            }

            return new Expr\FuncCall(
                new Name($callableName),
                [
                    new Arg(new Expr\Variable('value')),
                    new Arg(new Expr\Variable('context')),
                ]
            );
        }

        if ($metadata->mapperMetadata->sourceReflectionClass !== null && $metadata->mapperMetadata->sourceReflectionClass->hasMethod($propertyMetadata->if)) {
            $reflectionMethod = $metadata->mapperMetadata->sourceReflectionClass->getMethod($propertyMetadata->if);

            if ($reflectionMethod->isStatic()) {
                return new Expr\StaticCall(
                    new Name\FullyQualified($metadata->mapperMetadata->source),
                    $propertyMetadata->if,
                    [
                        new Arg(new Expr\Variable('value')),
                        new Arg(new Expr\Variable('context')),
                    ]
                );
            }

            return new Expr\MethodCall(
                new Expr\Variable('value'),
                $propertyMetadata->if,
                [
                    new Arg(new Expr\Variable('value')),
                    new Arg(new Expr\Variable('context')),
                ]
            );
        }

        $expression = $this->expressionLanguage->compile($propertyMetadata->if, ['value' => 'source', 'context']);
        $expr = $this->parser->parse('<?php ' . $expression . ';')[0] ?? null;

        if ($expr instanceof Stmt\Expression) {
            return $expr->expr;
        }

        throw new CompileException('Cannot use callback or create expression language condition from expression "' . $propertyMetadata->if . "'");
    }
}
