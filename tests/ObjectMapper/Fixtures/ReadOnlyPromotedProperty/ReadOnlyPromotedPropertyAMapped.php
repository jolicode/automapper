<?php

namespace AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty;

use Symfony\Component\ObjectMapper\Attribute\Map;

final class ReadOnlyPromotedPropertyAMapped
{
    public function __construct(
        public ReadOnlyPromotedPropertyBMapped $b,
        public string $var1,
    ) {
    }
}
