<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\IdentifierHash;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Attribute\Mapper;
use AutoMapper\Tests\AutoMapperBuilder;

#[Mapper(source: 'array', strictTypes: true)]
class Item
{
    #[MapFrom(source: 'array', identifier: true)]
    public int $id;

    public string $label = '';
}

class Basket
{
    /** @var Item[] */
    public array $items = [];
}

#[Mapper(source: 'array', strictTypes: true)]
class Ref
{
    #[MapFrom(source: 'array', identifier: true)]
    public string $value = '';
}

#[Mapper(source: 'array', strictTypes: true)]
class Product
{
    #[MapFrom(source: 'array', identifier: true)]
    public Ref $ref;

    public string $name = '';
}

class Catalog
{
    /** @var Product[] */
    public array $products = [];
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // int identifier hashed under strict types
    $itemOne = new Item();
    $itemOne->id = 1;
    $itemOne->label = 'one';

    $itemTwo = new Item();
    $itemTwo->id = 2;
    $itemTwo->label = 'two';

    $basket = new Basket();
    $basket->items = [$itemOne, $itemTwo];

    $data = [
        'items' => [
            ['id' => 1, 'label' => 'one updated'],
            ['id' => 3, 'label' => 'three'],
        ],
    ];

    yield 'int-identifier' => $autoMapper->map($data, $basket, ['deep_target_to_populate' => true]);

    // object identifier hashed through its own mapper
    $ref = new Ref();
    $ref->value = 'a';

    $product = new Product();
    $product->ref = $ref;
    $product->name = 'product a';

    $catalog = new Catalog();
    $catalog->products = [$product];

    $data = [
        'products' => [
            ['ref' => ['value' => 'a'], 'name' => 'product a updated'],
            ['ref' => ['value' => 'b'], 'name' => 'product b'],
        ],
    ];

    yield 'object-identifier' => $autoMapper->map($data, $catalog, ['deep_target_to_populate' => true]);
})();
