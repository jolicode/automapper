<?php

declare(strict_types=1);

namespace AutoMapper\Generator;

use AutoMapper\MapperContext;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem as NewArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayItem as OldArrayItem;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

/**
 * We generate a list of conditions that will allow the field to be mapped to the target.
 *
 * @internal
 */
final readonly class PropertyConditionsGenerator
{
    public function generate(GeneratorMetadata $metadata, PropertyMetadata $PropertyMetadata): ?Expr
    {
        $conditions = [];

        $conditions[] = $this->propertyExistsForStdClass($metadata, $PropertyMetadata);
        $conditions[] = $this->propertyExistsForArray($metadata, $PropertyMetadata);
        $conditions[] = $this->isAllowedAttribute($metadata, $PropertyMetadata);
        $conditions[] = $this->sourceGroupsCheck($metadata, $PropertyMetadata);
        $conditions[] = $this->targetGroupsCheck($metadata, $PropertyMetadata);
        $conditions[] = $this->maxDepthCheck($metadata, $PropertyMetadata);

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
    private function propertyExistsForStdClass(GeneratorMetadata $metadata, PropertyMetadata $PropertyMetadata): ?Expr
    {
        if (!$PropertyMetadata->source->checkExists || \stdClass::class !== $metadata->mapperMetadata->source) {
            return null;
        }

        return new Expr\FuncCall(new Name('property_exists'), [
            new Arg($metadata->variableRegistry->getSourceInput()),
            new Arg(new Scalar\String_($PropertyMetadata->source->name)),
        ]);
    }

    /**
     * In case of source is an array we ensure that the key exists.
     *
     * ```php
     * array_key_exists('propertyName', $source).
     * ```
     */
    private function propertyExistsForArray(GeneratorMetadata $metadata, PropertyMetadata $PropertyMetadata): ?Expr
    {
        if (!$PropertyMetadata->source->checkExists || 'array' !== $metadata->mapperMetadata->source) {
            return null;
        }

        return new Expr\FuncCall(new Name('array_key_exists'), [
            new Arg(new Scalar\String_($PropertyMetadata->source->name)),
            new Arg($metadata->variableRegistry->getSourceInput()),
        ]);
    }

    /**
     * In case of supporting attributes checking, we check if the property is allowed to be mapped.
     *
     * ```php
     * MapperContext::isAllowedAttribute($context, 'propertyName', isset($source->field)).
     * ```
     */
    private function isAllowedAttribute(GeneratorMetadata $metadata, PropertyMetadata $PropertyMetadata): ?Expr
    {
        if (!$PropertyMetadata->source->accessor || !$metadata->checkAttributes) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;

        return new Expr\StaticCall(new Name\FullyQualified(MapperContext::class), 'isAllowedAttribute', [
            new Arg($variableRegistry->getContext()),
            new Arg(new Scalar\String_($PropertyMetadata->source->name)),
            new Arg($PropertyMetadata->source->accessor->getIsNullExpression($variableRegistry->getSourceInput())),
        ]);
    }

    /**
     * When there are groups associated to the source property we check if the context has the same groups.
     *
     * ```php
     * (null !== $context[MapperContext::GROUPS] ?? null && array_intersect($context[MapperContext::GROUPS] ?? [], ['group1', 'group2']))
     * ```
     */
    private function sourceGroupsCheck(GeneratorMetadata $metadata, PropertyMetadata $PropertyMetadata): ?Expr
    {
        if (!$PropertyMetadata->source->groups) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;

        // compatibility with old versions of nikic/php-parser
        if (class_exists(NewArrayItem::class)) {
            $arrayItemClass = NewArrayItem::class;
        } else {
            $arrayItemClass = OldArrayItem::class;
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
                new Arg(new Expr\Array_(array_map(function (string $group) use ($arrayItemClass) {
                    return new $arrayItemClass(new Scalar\String_($group));
                }, $PropertyMetadata->source->groups))),
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
    private function targetGroupsCheck(GeneratorMetadata $metadata, PropertyMetadata $PropertyMetadata): ?Expr
    {
        if (!$PropertyMetadata->target->groups) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;

        // compatibility with old versions of nikic/php-parser
        if (class_exists(NewArrayItem::class)) {
            $arrayItemClass = NewArrayItem::class;
        } else {
            $arrayItemClass = OldArrayItem::class;
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
                new Arg(new Expr\Array_(array_map(function (string $group) use ($arrayItemClass) {
                    return new $arrayItemClass(new Scalar\String_($group));
                }, $PropertyMetadata->target->groups))),
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
    private function maxDepthCheck(GeneratorMetadata $metadata, PropertyMetadata $PropertyMetadata): ?Expr
    {
        if (!$PropertyMetadata->maxDepth) {
            return null;
        }

        $variableRegistry = $metadata->variableRegistry;

        return new Expr\BinaryOp\SmallerOrEqual(
            new Expr\BinaryOp\Coalesce(
                new Expr\ArrayDimFetch($variableRegistry->getContext(), new Scalar\String_(MapperContext::DEPTH)),
                new Expr\ConstFetch(new Name('0'))
            ),
            new Scalar\LNumber($PropertyMetadata->maxDepth)
        );
    }
}
