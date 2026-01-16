<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLoadedValue;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: LoadedValueTarget::class)]
final class ValueToMap
{
    public ?ValueToMapRelation $relation;
}
