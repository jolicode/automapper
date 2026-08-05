<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class CircularNode
{
    public ?CircularNode $next = null;

    public function __construct(
        public string $name = '',
    ) {
    }
}
