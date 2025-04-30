<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class Category
{
    /** @var Post[] */
    public array $posts = [];

    public function __construct(
        public string $name
    ) {
    }
}
