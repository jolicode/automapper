<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\LazyMapSingleInitialization;

use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;

final class ReadCounter
{
    public static int $count = 0;
}

class Source
{
    private string $name = 'foo';

    public function getName(): string
    {
        ++ReadCounter::$count;

        return $this->name;
    }
}

$map = AutoMapperBuilder::buildAutoMapper()->map(new Source(), 'array', [MapperContext::LAZY_MAPPING => true]);

// the mapping must run exactly once, whatever the number of accesses
$first = $map['name'];
$second = $map['name'];
$map['extra'] = 'bar';
$exists = isset($map['name']);

return [
    'name' => $map['name'],
    'extra' => $map['extra'],
    'reads' => ReadCounter::$count,
];
