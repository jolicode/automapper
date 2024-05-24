<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapTo;

class DateTimeFormatMapTo
{
    public function __construct(
        #[MapTo('array', dateTimeFormat: \DateTimeInterface::ATOM)]
        public \DateTime $normal,
        #[MapTo('array', dateTimeFormat: \DateTimeInterface::RFC822)]
        public \DateTimeImmutable $immutable,
        #[MapTo('array', dateTimeFormat: \DateTimeInterface::RFC7231)]
        public \DateTimeInterface $interface,
    ) {
    }
}
