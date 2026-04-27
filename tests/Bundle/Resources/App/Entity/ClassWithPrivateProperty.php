<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Entity;

class ClassWithPrivateProperty
{
    /** @var Address[] */
    private array $addresses = [];

    public function __construct(
        private string $foo,
        array $addresses = [],
    ) {
        $this->addresses = $addresses;
    }

    private function getBar(): string
    {
        return 'bar';
    }
}
