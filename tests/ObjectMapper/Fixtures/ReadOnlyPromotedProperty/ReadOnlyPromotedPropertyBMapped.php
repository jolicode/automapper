<?php

namespace AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty;

use Symfony\Component\ObjectMapper\Attribute\Map;

final class ReadOnlyPromotedPropertyBMapped
{
    public function __construct(
        public string $var2,
    ) {
    }
}
