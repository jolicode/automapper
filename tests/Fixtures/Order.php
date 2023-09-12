<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use Money\Money;

class Order
{
    /** @var int */
    public $id;

    /** @var Money */
    public $price;
}
