<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\LazyCollectionMapping;

use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;

class Item
{
    public function __construct(
        public string $name,
    ) {
    }
}

class Bag
{
    /** @var Item[] */
    public array $items = [];

    /** @var iterable<string> */
    public iterable $tags = [];
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $bag = new Bag();
    $bag->items = [new Item('a'), new Item('b')];
    $bag->tags = ['x', 'y', 'z'];

    // Eager mapping to array: nested collections are materialized.
    yield 'eager' => $autoMapper->map($bag, 'array');

    // Lazy mapping produces a LazyMap holding LazyCollection nodes; resolving it (both are
    // JsonSerializable) must yield exactly the same structure as the eager mapping.
    $lazy = $autoMapper->map($bag, 'array', [MapperContext::LAZY_MAPPING => true]);

    yield 'lazy' => json_decode(json_encode($lazy), true);
})();
