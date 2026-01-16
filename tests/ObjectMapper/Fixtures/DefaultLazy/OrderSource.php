<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\DefaultLazy;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: OrderTarget::class)]
class OrderSource
{
    public ?int $id = null;
    public ?UserSource $user = null;
}
