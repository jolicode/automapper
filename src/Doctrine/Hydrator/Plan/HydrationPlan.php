<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator\Plan;

/**
 * Result of the analysis of a ResultSetMapping: all decisions the generated hydrator is built from.
 *
 * @internal
 */
final readonly class HydrationPlan
{
    /** Replaces the ObjectHydrator (HYDRATE_OBJECT) */
    public const OBJECT = 'object';
    /** Replaces the SimpleObjectHydrator (HYDRATE_SIMPLEOBJECT), used by the entity persisters */
    public const SIMPLE = 'simple';

    public function __construct(
        public string $mode,
        /** @var list<AliasPlan> ordered like the result set mapping, parents first */
        public array $aliases,
        /** @var list<class-string> */
        public array $classes,
        /** @var array<string, array<string, true>> associations fetch joined by the query, as computed by the ObjectHydrator */
        public array $fetched,
        /** @var class-string */
        public string $platform,
    ) {
    }

    public function hash(): string
    {
        return hash('xxh128', serialize($this));
    }
}
