<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ObjectProvider;

use AutoMapper\Provider\ProviderInterface;

class FooProvider implements ProviderInterface
{
    public function provide(string $targetType, mixed $source, array $context): object|null
    {
        return new Foo('World');
    }
}