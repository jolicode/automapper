<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Api\Entity;

use ApiPlatform\Metadata\ApiResource;
use AutoMapper\Attribute\Mapper;
use AutoMapper\Tests\Bundle\Resources\App\Api\Processor\ReviewProcessor;

/** A book. */
#[ApiResource(processor: ReviewProcessor::class)]
#[Mapper(source: 'array', target: 'array')]
class Review
{
    /** The ID of this review. */
    private ?int $id = null;

    /** The rating of this review (between 0 and 5). */
    public int $rating = 0;

    /** The body of the review. */
    public string $body = '';

    /** The author of the review. */
    public string $author = '';

    /** The date of publication of this review.*/
    public ?\DateTimeImmutable $publicationDate = null;

    /** The book this review is about. */
    public ?Book $book = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
