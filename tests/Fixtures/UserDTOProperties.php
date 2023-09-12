<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class UserDTOProperties
{
    /**
     * @var mixed[]
     */
    protected $properties = [];

    public function getProperties(): iterable
    {
        return $this->properties;
    }

    public function setProperties(iterable $properties): void
    {
        $this->properties = $properties;
    }
}
