<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty;

final class ReadOnlyPromotedPropertyAMapped
{
    public function __construct(
        public ReadOnlyPromotedPropertyBMapped $b,
        public string $var1,
    ) {
    }
}
