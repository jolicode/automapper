<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

final class HasDateTimeInterfaceWithMutableInstance
{
    public \DateTimeInterface $dateTime;

    public static function create(): self
    {
        $self = new self();
        $self->dateTime = new \DateTime('2024-01-01 00:00:00');

        return $self;
    }

    public function getString(): string
    {
        return $this->dateTime->format(\DateTime::ATOM);
    }

}
