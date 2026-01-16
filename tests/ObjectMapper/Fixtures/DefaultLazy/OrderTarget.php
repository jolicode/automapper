<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\DefaultLazy;

class OrderTarget
{
    public ?int $id = null;
    public ?UserTarget $user = null;
}
