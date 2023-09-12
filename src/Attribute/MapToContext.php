<?php

declare(strict_types=1);

namespace AutoMapper\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class MapToContext
{
    public function __construct(
        public readonly string $contextName,
    ) {
    }
}
