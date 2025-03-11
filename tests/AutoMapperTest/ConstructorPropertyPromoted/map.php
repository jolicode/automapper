<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ConstructorPropertyPromoted;

use AutoMapper\Tests\AutoMapperBuilder;

class AddressDTO
{
    /**
     * @var string|null
     */
    public $city;
}

readonly class UserPromoted
{
    /**
     * @param array<AddressDTO> $addresses
     */
    public function __construct(
        public array $addresses
    ) {
    }
}

$address = new AddressDTO();
$address->city = 'city';

$object = new UserPromoted([$address, $address]);

return AutoMapperBuilder::buildAutoMapper()->map($object, 'array');
