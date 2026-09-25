<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator\Plan;

/**
 * How a value is written on an entity, mirroring the semantics of Doctrine property accessors.
 *
 * @internal
 */
final readonly class PropertyWrite
{
    public function __construct(
        public string $property,
        /** Whether the property can be written directly from the entity class scope, otherwise the Doctrine accessor is used */
        public bool $direct,
        /** Doctrine unsets non-nullable typed properties instead of assigning null to them */
        public bool $unsetOnNull,
        /** Unsetting only matters when the property has a default value, otherwise it is already uninitialized */
        public bool $hasDefault,
    ) {
    }
}
