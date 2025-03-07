<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapFrom;

class ManySourceBar
{
    public function __construct(
        public string $dateEffet = '',
    ) {
    }
}
