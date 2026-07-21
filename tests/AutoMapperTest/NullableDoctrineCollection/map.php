<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\NullableDoctrineCollection;

use AutoMapper\Tests\AutoMapperBuilder;
use Doctrine\Common\Collections\Collection;

class Item
{
    public string $name = '';
}

class ItemDto
{
    public string $name = '';
}

class Source
{
    /** @var Item[] */
    public array $items = [];
}

class Target
{
    /** @var ?Collection<int, ItemDto> */
    public ?Collection $items = null;
}

$item = new Item();
$item->name = 'foo';

$source = new Source();
$source->items = [$item];

return AutoMapperBuilder::buildAutoMapper()->map($source, Target::class);
