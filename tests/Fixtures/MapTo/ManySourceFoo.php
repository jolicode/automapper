<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapFrom;

class ManySourceFoo
{
    public function __construct(
        public string $dateDebutEffet = '',
    ) {
    }
}
