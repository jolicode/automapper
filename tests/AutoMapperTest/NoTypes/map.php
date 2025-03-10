<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\NoTypes;

use AutoMapper\Tests\AutoMapperBuilder;

class AddressNoTypes
{
    public $city;
}

$address = new AddressNoTypes();
$address->city = 'test';

$autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'NotReadable_');

return $autoMapper->map($address, 'array');
