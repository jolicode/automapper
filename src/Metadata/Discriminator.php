<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

final readonly class Discriminator
{
    public function __construct(
        /** @var array<string, class-string<object>> */
        public array $mapping,
        public ?string $propertyName = null,
    ) {
    }
}
