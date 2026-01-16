<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\DefaultLazy;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: UserTarget::class)]
class UserSource
{
    public ?string $name = null;
}
