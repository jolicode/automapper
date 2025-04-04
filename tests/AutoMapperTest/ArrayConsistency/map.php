<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayConsistency;

use AutoMapper\Tests\AutoMapperBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class From
{
    public array $values;
}

class To
{
    public array $values;
}

class ToAdder
{
    private array $values;

    public function addValue(mixed $value): void
    {
        $this->values[] = $value;
    }

    public function removeValue(mixed $value): void
    {
        foreach ($this->values as $key => $existing) {
            if ($existing === $value) {
                unset($this->values[$key]);
                $this->values = array_values($this->values);
            }
        }
    }

    public function getValues(): array
    {
        return $this->values;
    }
}

class ToCollection
{
    public ArrayCollection $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }
}

class ToAdderCollection
{
    private Collection $values;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    public function addValue(mixed $value): void
    {
        $this->values->add($value);
    }

    public function removeValue(mixed $value): void
    {
        $this->values->removeElement($value);
    }

    public function getValues(): Collection
    {
        return $this->values;
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $from = new From();
    $from->values = [1, 2, 3];

    $to = new To();
    $to->values = [4, 5, 6];

    $toAdder = new ToAdder();
    $toAdder->addValue(4);
    $toAdder->addValue(5);
    $toAdder->addValue(6);

    $toAdderCollection = new ToAdderCollection();
    $toAdderCollection->addValue(4);
    $toAdderCollection->addValue(5);
    $toAdderCollection->addValue(6);

    $toCollection = new ToCollection();
    $toCollection->values->add(4);
    $toCollection->values->add(5);
    $toCollection->values->add(6);

    yield 'to' => $autoMapper->map($from, $to);

    yield 'toAdder' => $autoMapper->map($from, $toAdder);

    yield 'toAdderCollection' => $autoMapper->map($from, $toAdderCollection);

    yield 'toCollection' => $autoMapper->map($from, $toCollection);
})();
