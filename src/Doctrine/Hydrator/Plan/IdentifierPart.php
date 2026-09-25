<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator\Plan;

/**
 * One field of an entity identifier, as flattened by the unit of work.
 *
 * @internal
 */
final readonly class IdentifierPart
{
    public function __construct(
        public string $field,
        /** Data key holding the value: the field itself, or the join column of an association identifier */
        public string $key,
        public bool $association,
    ) {
    }
}
