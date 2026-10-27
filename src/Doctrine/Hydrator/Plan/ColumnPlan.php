<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator\Plan;

/**
 * A column of the result set belonging to an alias: a mapped field or a meta column (foreign key).
 *
 * @internal
 */
final readonly class ColumnPlan
{
    public function __construct(
        public string $column,
        /** Key in the entity data given to the unit of work: the field name or the join column name */
        public string $key,
        public ?string $type,
        /** @var class-string<\BackedEnum>|null */
        public ?string $enumType = null,
        /** Null for meta columns, which are not written on the entity */
        public ?PropertyWrite $write = null,
    ) {
    }
}
