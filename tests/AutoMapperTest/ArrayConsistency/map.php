<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayConsistency;

use AutoMapper\Tests\AutoMapperBuilder;

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

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $from = new From();
    $to = new To();
    $toAdder = new ToAdder();
    $from->values = [1, 2, 3];
    $to->values = [4, 5, 6];
    $toAdder->addValue(4);
    $toAdder->addValue(5);
    $toAdder->addValue(6);

    yield 'to' => $autoMapper->map($from, $to);

    yield 'toAdder' => $autoMapper->map($from, $toAdder);
})();
