<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Api\Entity;

use ApiPlatform\Metadata\ApiResource;
use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\Bundle\Resources\App\Api\Processor\BookProcessor;
use AutoMapper\Tests\Bundle\Resources\App\Api\Provider\BookProvider;

/** A book. */
#[ApiResource(provider: BookProvider::class, processor: BookProcessor::class)]
class Book
{
    /** The ID of this book. */
    public ?int $id = null;

    /** The title of this book. */
    public string $title = '';

    /** The description of this book. */
    public string $description = '';

    /** The author of this book. */
    public string $author = '';

    /** The publication date of this book. */
    #[MapTo(if: 'source.publicationDate !== null')]
    public ?\DateTimeImmutable $publicationDate = null;

    /** @var Review[] Available reviews for this book. */
    public iterable $reviews;

    public function __construct()
    {
        $this->reviews = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
