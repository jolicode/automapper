<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\MapperContext;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

/**
 * We generate a list of conditions that will allow the field to be mapped to the target.
 *
 * @internal
 */
final readonly class PropertyConditionsGenerator
{
    public function generate(PropertyMapping $propertyMapping): ?Expr
    {
        $conditions = [];

        $conditions[] = $this->propertyExistsForStdClass($propertyMapping);
        $conditions[] = $this->propertyExistsForArray($propertyMapping);
        $conditions[] = $this->isAllowedAttribute($propertyMapping);
        $conditions[] = $this->sourceGroupsCheck($propertyMapping);
        $conditions[] = $this->targetGroupsCheck($propertyMapping);
        $conditions[] = $this->maxDepthCheck($propertyMapping);

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
    private function propertyExistsForStdClass(PropertyMapping $propertyMapping): ?Expr
    {
        $variableRegistry = $propertyMapping->mapperMetadata->getVariableRegistry();

        if (!$propertyMapping->checkExists || \stdClass::class !== $propertyMapping->mapperMetadata->getSource()) {
            return null;
        }

        return new Expr\FuncCall(new Name('property_exists'), [
            new Arg($variableRegistry->getSourceInput()),
            new Arg(new Scalar\String_($propertyMapping->property)),
        ]);
    }

    /**
     * In case of source is an array we ensure that the key exists.
     *
     * ```php
     * array_key_exists('propertyName', $source).
     * ```
     */
    private function propertyExistsForArray(PropertyMapping $propertyMapping): ?Expr
    {
        if (!$propertyMapping->checkExists || 'array' !== $propertyMapping->mapperMetadata->getSource()) {
            return null;
        }

        $variableRegistry = $propertyMapping->mapperMetadata->getVariableRegistry();

        return new Expr\FuncCall(new Name('array_key_exists'), [
            new Arg(new Scalar\String_($propertyMapping->property)),
            new Arg($variableRegistry->getSourceInput()),
        ]);
    }

    /**
     * In case of supporting attributes checking, we check if the property is allowed to be mapped.
     *
     * ```php
     * MapperContext::isAllowedAttribute($context, 'propertyName', $source).
     * ```
     */
    private function isAllowedAttribute(PropertyMapping $propertyMapping): ?Expr
    {
        $mapperMetadata = $propertyMapping->mapperMetadata;

        if (!$propertyMapping->readAccessor || !$mapperMetadata->shouldCheckAttributes()) {
            return null;
        }

        $variableRegistry = $mapperMetadata->getVariableRegistry();

        /** Create expression on how to read the value from the source */
        $sourcePropertyAccessor = new Expr\Assign(
            $variableRegistry->getFieldValueVariable($propertyMapping),
            $propertyMapping->readAccessor->getExpression($variableRegistry->getSourceInput())
        );

        return new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'isAllowedAttribute', [
            new Arg($variableRegistry->getContext()),
            new Arg(new Scalar\String_($propertyMapping->property)),
            new Arg($sourcePropertyAccessor),
        ]);
    }

    /**
     * When there are groups associated to the source property we check if the context has the same groups.
     *
     * ```php
     * (null !== $context[MapperContext::GROUPS] ?? null && array_intersect($context[MapperContext::GROUPS] ?? [], ['group1', 'group2']))
     * ```
     */
    private function sourceGroupsCheck(PropertyMapping $propertyMapping): ?Expr
    {
        if (!$propertyMapping->sourceGroups) {
            return null;
        }

        $variableRegistry = $propertyMapping->mapperMetadata->getVariableRegistry();

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
                new Arg(new Expr\Array_(array_map(function (string $group) {
                    return new Expr\ArrayItem(new Scalar\String_($group));
                }, $propertyMapping->sourceGroups))),
            ])
        );
    }

    /**
     * When there is groups associated to the target property we check if the context has the same groups.
     *
     * ```php
     * (null !== $context[MapperContext::GROUPS] ?? null && array_intersect($context[MapperContext::GROUPS] ?? [], ['group1', 'group2']))
     * ```
     */
    private function targetGroupsCheck(PropertyMapping $propertyMapping): ?Expr
    {
        if (!$propertyMapping->targetGroups) {
            return null;
        }

        $variableRegistry = $propertyMapping->mapperMetadata->getVariableRegistry();

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
                new Arg(new Expr\Array_(array_map(function (string $group) {
                    return new Expr\ArrayItem(new Scalar\String_($group));
                }, $propertyMapping->targetGroups))),
            ])
        );
    }

    /**
     * When there is a max depth for this property we check if the context has a depth lower or equal to the max depth.
     *
     * ```php
     * ($context[MapperContext::DEPTH] ?? 0) <= $maxDepth
     * ```
     */
    private function maxDepthCheck(PropertyMapping $propertyMapping): ?Expr
    {
        if (!$propertyMapping->maxDepth) {
            return null;
        }

        $variableRegistry = $propertyMapping->mapperMetadata->getVariableRegistry();

        return new Expr\BinaryOp\SmallerOrEqual(
            new Expr\BinaryOp\Coalesce(
                new Expr\ArrayDimFetch($variableRegistry->getContext(), new Scalar\String_(MapperContext::DEPTH)),
                new Expr\ConstFetch(new Name('0'))
            ),
            new Scalar\LNumber($propertyMapping->maxDepth)
        );
    }
}
