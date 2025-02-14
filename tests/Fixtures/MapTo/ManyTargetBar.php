<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapFrom;

class ManyTargetBar
{
    public function __construct(
        #[MapFrom(source: 'array', property: 'dateEffet')]
        #[MapFrom(source: 'array', property: 'dateEffetDeux')]
        #[MapFrom(source: ManySourceFoo::class, property: 'dateDebutEffet')]
        public string $foo = '',
    ) {
    }
}
