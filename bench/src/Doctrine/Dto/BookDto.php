<?php

declare(strict_types=1);

namespace Automapper\Bench\Doctrine\Dto;

class BookDto
{
    public int $id;
    public string $title;
    public string $isbn;
    public int $pages;
    public float $price;
    public \DateTimeImmutable $publishedAt;
    /** @var ReviewDto[] */
    public array $reviews = [];
}
