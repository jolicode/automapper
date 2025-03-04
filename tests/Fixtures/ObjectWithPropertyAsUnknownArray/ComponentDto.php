<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ObjectWithPropertyAsUnknownArray;

final readonly class ComponentDto
{
    public function __construct(
        public string $name,
    ) {
    }
}
