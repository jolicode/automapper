<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DeepPopulateMergeExisting;

use AutoMapper\Attribute\MapIdentifier;
use AutoMapper\Tests\AutoMapperBuilder;
use Doctrine\Common\Collections\ArrayCollection;

class Bar
{
    #[MapIdentifier]
    private int $id;

    public string $bar;

    public function setId(int $id): void
    {
        $this->id = $id;
    }
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
    $autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

    $data = [
        'bars' => [
            ['bar' => 'bar3', 'id' => 1],
            ['bar' => 'bar4', 'id' => 10],
        ],
    ];

    $bar1 = new Bar();
    $bar1->bar = 'bar1';
    $bar1->setId(1);

    $bar2 = new Bar();
    $bar2->bar = 'bar2';
    $bar2->setId(2);

    $existingObject = new Foo();
    $existingObject->bars = [$bar1, $bar2];

    $existingObjectWithArrayCollection = new FooWithArrayCollection();
    $existingObjectWithArrayCollection->bars = new ArrayCollection([$bar1, $bar2]);

    yield 'array' => $autoMapper->map($data, $existingObject, ['deep_target_to_populate' => true]);

    yield 'collection' => $autoMapper->map($data, $existingObjectWithArrayCollection, ['deep_target_to_populate' => true]);
})();
