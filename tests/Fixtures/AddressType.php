<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

enum AddressType: string
{
    case FLAT = 'flat';
    case APARTMENT = 'apartment';
}
