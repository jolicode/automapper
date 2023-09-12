<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class AddressNotWritable
{
    /**
     * @var string|null
     */
    private $city;

    public function getCity(): ?string
    {
        return $this->city;
    }
}
