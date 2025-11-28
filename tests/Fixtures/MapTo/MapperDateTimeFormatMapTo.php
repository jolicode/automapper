<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\Mapper;
use AutoMapper\Attribute\MapTo;

#[Mapper(dateTimeFormat: \DateTimeInterface::ATOM)]
class MapperDateTimeFormatMapTo
{
    public function __construct(
        public \DateTime $normal,
        public \DateTimeImmutable $immutable,
        #[MapTo('array', dateTimeFormat: \DateTimeInterface::RFC3339_EXTENDED)]
        public \DateTimeInterface $interface,
    ) {
    }
}
