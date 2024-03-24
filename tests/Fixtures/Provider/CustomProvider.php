<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Provider;

use AutoMapper\Provider\ProviderInterface;

final readonly class CustomProvider implements ProviderInterface
{
    public function __construct(
        private object|array|null $value
    ) {
    }

    public function provide(string $targetType, mixed $source, array $context): object|array|null
    {
        return $this->value;
    }
}
