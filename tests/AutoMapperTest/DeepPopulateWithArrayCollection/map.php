<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DeepPopulateWithArrayCollection;

use AutoMapper\Tests\AutoMapperBuilder;
use Doctrine\Common\Collections\ArrayCollection;

class Bar
{
    public string $bar;
}

class Foo
{
    /** @var array<Bar> */
    public array $bars;
}

class FooWithArrayCollection
{
    /** @var ArrayCollection<Bar> */
    public ArrayCollection $bars;
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $data = [
        'bars' => [
            ['bar' => 'bar3'],
            ['bar' => 'bar4'],
        ],
    ];

    $bar1 = new Bar();
    $bar1->bar = 'bar1';

    $bar2 = new Bar();
    $bar2->bar = 'bar2';

    $existingObject = new Foo();
    $existingObject->bars = [$bar1, $bar2];

    $existingObjectWithArrayCollection = new FooWithArrayCollection();
    $existingObjectWithArrayCollection->bars = new ArrayCollection([$bar1, $bar2]);

    yield 'array' => $autoMapper->map($data, $existingObject, ['deep_target_to_populate' => true]);

    yield 'collection' => $autoMapper->map($data, $existingObjectWithArrayCollection, ['deep_target_to_populate' => true]);
})();
