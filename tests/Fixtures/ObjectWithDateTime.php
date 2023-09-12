<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

final class ObjectWithDateTime
{
    /**
     * @var \DateTimeInterface
     */
    public $dateTime;

    public function __construct(\DateTimeInterface $dateTime)
    {
        $this->dateTime = $dateTime;
    }
}
