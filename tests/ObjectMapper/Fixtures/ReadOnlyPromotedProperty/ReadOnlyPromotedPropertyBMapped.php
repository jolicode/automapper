<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty;

final class ReadOnlyPromotedPropertyBMapped
{
    public function __construct(
        public string $var2,
    ) {
    }
}
