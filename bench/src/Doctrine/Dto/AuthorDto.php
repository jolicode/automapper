<?php

declare(strict_types=1);

namespace Automapper\Bench\Doctrine\Dto;

class AuthorDto
{
    public int $id;
    public string $name;
    public string $email;
    public bool $active;
    public \DateTimeImmutable $createdAt;
    /** @var BookDto[] */
    public array $books = [];
}
