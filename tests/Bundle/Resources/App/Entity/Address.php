<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Entity;

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
