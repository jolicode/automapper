<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class CategoryDTO
{
    /**
     * @var PostDTO[]
     */
    public array $posts = [];

    public function __construct(
        public string $name,
    ) {
    }
}
