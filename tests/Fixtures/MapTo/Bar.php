<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapFrom;

class Bar
{
    #[MapFrom(source: 'array', ignore: true)]
    private string $b = '';

    public function __construct(
        public string $bar,
        public string $baz,
        #[MapFrom(name: 'foo')]
        public string $from,
    ) {
    }

    #[MapFrom(source: FooMapTo::class, name: 'd')]
    public function setB(string $b)
    {
        $this->b = $b;
    }

    public function getB(): string
    {
        return $this->b;
    }
}
