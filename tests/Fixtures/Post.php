<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class Post
{
    public function __construct(
        public string $name,
        public Category $category
    ) {
    }
}
