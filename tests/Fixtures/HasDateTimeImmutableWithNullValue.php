<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

final class HasDateTimeImmutableWithNullValue
{
    public ?\DateTimeImmutable $dateTime = null;

    public static function create(): self
    {
        return new self();
    }

    public function getString(): ?string
    {
        return $this->dateTime?->format(\DateTime::ATOM);
    }
}
