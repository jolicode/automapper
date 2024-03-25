<?php

declare(strict_types=1);

namespace AutoMapper\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
final readonly class MapProvider
{
    /**
     * @param ?string $source   from which source type this provider should apply
     * @param string  $provider the provider class name or service identifier
     */
    public function __construct(
        public string $provider,
        public ?string $source = null,
    ) {
    }
}
