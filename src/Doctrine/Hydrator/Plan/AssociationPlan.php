<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator\Plan;

/**
 * An association of a freshly created entity that the generated code initializes itself.
 *
 * @internal
 */
final readonly class AssociationPlan
{
    /** Not fetch joined to-one owning side: a reference built from the foreign key columns */
    public const REFERENCE = 'reference';
    /** Not fetch joined to-many: an uninitialized persistent collection */
    public const LAZY_COLLECTION = 'lazy_collection';
    /** Fetch joined to-many: an initialized persistent collection filled by the child alias */
    public const FETCHED_COLLECTION = 'fetched_collection';

    public function __construct(
        public string $kind,
        public string $field,
        public int $classIndex,
        public int $targetClassIndex,
        public PropertyWrite $write,
        /** @var array<string, string> target identifier field => data key of the foreign key, in join column order */
        public array $foreignKeys = [],
    ) {
    }
}
