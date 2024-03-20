<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class DifferentSetterGetterType
{
    private AddressType $addressDocBlock;

    public function __construct(
        private AddressType $address
    ) {
        $this->addressDocBlock = $address;
    }

    public function getAddress(): string
    {
        return $this->address->value;
    }

    public function getAddressDocBlock(): string
    {
        return $this->addressDocBlock->value;
    }

    public function setAddress(AddressType $address): void
    {
        $this->address = $address;
    }
}
