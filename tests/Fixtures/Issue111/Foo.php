<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Issue111;

class Foo
{
    private array $colours = [];

    public function getColours(): array
    {
        return $this->colours;
    }

    public function addColour(Colour $colour): void
    {
        $this->colours[] = $colour;
    }

    public function removeColour(Colour $colour): void
    {
        $key = array_search($colour, $this->colours, true);

        if ($key !== false) {
            unset($this->colours[$key]);
        }
    }
}
