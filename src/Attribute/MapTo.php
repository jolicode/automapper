<?php

declare(strict_types=1);

namespace AutoMapper\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class MapTo
{
    /**
     * @param non-empty-string          $propertyName
     * @param 'array'|class-string|null $target
     */
    public function __construct(
        public string $propertyName,
        public string|null $target = null,
    ) {
    }
}
