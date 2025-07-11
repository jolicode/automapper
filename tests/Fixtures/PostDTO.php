<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class PostDTO
{
    public function __construct(
        public string $name,
        public CategoryDTO $category,
    ) {
    }
}
