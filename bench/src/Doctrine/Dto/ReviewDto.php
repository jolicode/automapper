<?php

declare(strict_types=1);

namespace Automapper\Bench\Doctrine\Dto;

class ReviewDto
{
    public int $id;
    public int $rating;
    public string $comment;
    public \DateTimeImmutable $createdAt;
}
