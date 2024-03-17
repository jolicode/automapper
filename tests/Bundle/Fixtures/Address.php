<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Fixtures;

class Address
{
    /**
     * @var string|null
     */
    private $city;

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }
}
