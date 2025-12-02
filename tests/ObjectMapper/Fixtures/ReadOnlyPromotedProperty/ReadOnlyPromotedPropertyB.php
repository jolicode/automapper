<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: ReadOnlyPromotedPropertyBMapped::class)]
final class ReadOnlyPromotedPropertyB
{
    public function __construct(
        public string $var2,
    ) {
    }
}
