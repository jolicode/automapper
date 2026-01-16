<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: ReadOnlyPromotedPropertyAMapped::class)]
final class ReadOnlyPromotedPropertyA
{
    public function __construct(
        public ReadOnlyPromotedPropertyB $b,
        public string $var1,
    ) {
    }
}
