<?php

declare(strict_types=1);

namespace Automapper\Bench\ObjectMapping;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: AddressTarget::class)]
class AddressSource
{
    public string $street = '';
    public string $city = '';
    public string $zipCode = '';
    public string $country = '';
}
