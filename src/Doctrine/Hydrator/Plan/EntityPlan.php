<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator\Plan;

/**
 * How entities of one concrete class are created for an alias. An alias of an inheritance hierarchy has one
 * plan per class of the discriminator map.
 *
 * @internal
 */
final readonly class EntityPlan
{
    public function __construct(
        public int $classIndex,
        /** @var class-string */
        public string $className,
        /** @var list<ColumnPlan> columns of the row that belong to this class, in result set order */
        public array $columns,
        /** @var list<AssociationPlan> */
        public array $associations,
        /** Set when a child of a one-to-many collection gets its back reference written at creation */
        public ?PropertyWrite $backReference,
        public ?string $backReferenceField,
        /** When set, entities of this class are always created by the unit of work */
        public ?string $slowReason,
        public bool $cloneable,
    ) {
    }

    public function association(string $field): ?AssociationPlan
    {
        foreach ($this->associations as $association) {
            if ($association->field === $field) {
                return $association;
            }
        }

        return null;
    }
}
