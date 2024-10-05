<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\MapTo;

use AutoMapper\Attribute\MapFrom;

class Bar
{
    #[MapFrom(source: 'array', ignore: true)]
    private string $b = '';

    #[MapFrom(source: 'array', transformer: 'transformC')]
    public string $c = '';

    #[MapFrom(source: 'array', transformer: 'transformDStatic')]
    public string $d = '';

    public function __construct(
        public string $bar,
        public string $baz,
        #[MapFrom(property: 'foo', transformer: 'htmlspecialchars')]
        public string $from,
    ) {
    }

    #[MapFrom(source: FooMapTo::class, property: 'd')]
    public function setB(string $b)
    {
        $this->b = $b;
    }

    public function getB(): string
    {
        return $this->b;
    }

    public function transformC(string $c, array $source, array $context): string
    {
        return 'transformC_' . $c;
    }

    public static function transformDStatic(string $c, array $source, array $context): string
    {
        return 'transformDStatic_' . $c;
    }
}
