<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DeepPopulateMergeExisting;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Tests\AutoMapperBuilder;
use Doctrine\Common\Collections\ArrayCollection;

class Bar
{
    #[MapFrom(source: 'array', identifier: true)]
    private int $id;

    public string $bar;

    public string $foo = 'default';

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}

class Foo
{
    /** @var array<Bar> */
    public array $bars;
}

class FooAdder
{
    /** @var array<Bar> */
    private array $bars;

    public function addBar(Bar $value): void
    {
        $this->bars[] = $value;
    }

    public function removeBar(Bar $value): void
    {
        foreach ($this->bars as $key => $existing) {
            if ($existing->getId() === $value->getId()) {
                unset($this->bars[$key]);
                $this->bars = array_values($this->bars);
            }
        }
    }

    public function getBars(): array
    {
        return $this->bars;
    }
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
    $bar1->foo = 'foo1';
    $bar1->setId(1);

    $bar2 = new Bar();
    $bar2->bar = 'bar2';
    $bar2->setId(2);

    $existingObject = new Foo();
    $existingObject->bars = [$bar1, $bar2];

    $existingObjectWithArrayCollection = new FooWithArrayCollection();
    $existingObjectWithArrayCollection->bars = new ArrayCollection([$bar1, $bar2]);

    $existingObjectWithAdder = new FooAdder();
    $existingObjectWithAdder->addBar($bar1);
    $existingObjectWithAdder->addBar($bar2);

    yield 'array' => $autoMapper->map($data, $existingObject, ['deep_target_to_populate' => true]);

    yield 'collection' => $autoMapper->map($data, $existingObjectWithArrayCollection, ['deep_target_to_populate' => true]);

    yield 'adder' => $autoMapper->map($data, $existingObjectWithAdder, ['deep_target_to_populate' => true]);
})();
