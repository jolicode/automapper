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
    public function __construct(
        public ?string $source = null,
        public ?string $target = null,
        public ?bool $checkAttributes = null,
        public ?ConstructorStrategy $constructorStrategy = null,
        public ?bool $allowReadOnlyTargetToPopulate = null,
        public int $priority = 0,
    ) {
    }
}
