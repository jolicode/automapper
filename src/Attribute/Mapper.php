<?php

declare(strict_types=1);

namespace AutoMapper\Attribute;

use AutoMapper\ConstructorStrategy;

/**
 * Configures a mapper.
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
final readonly class Mapper
{
    /**
     * @param class-string<object>|'array'|array<class-string<object>|'array'>|null $source the source class or classes
     * @param class-string<object>|'array'|array<class-string<object>|'array'>|null $target the target class or classes
     */
    public function __construct(
        public string|array|null $source = null,
        public string|array|null $target = null,
        public ?bool $checkAttributes = null,
        public ?ConstructorStrategy $constructorStrategy = null,
        public ?bool $allowReadOnlyTargetToPopulate = null,
        public int $priority = 0,
    ) {
    }
}
