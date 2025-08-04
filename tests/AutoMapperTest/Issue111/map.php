<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Issue111;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class FooDto
{
    #[MapTo(target: Foo::class, transformer: ColourTransformer::class)]
    public array $colours = [];
}

class Colour
{
    public function __construct(
        public string $name,
    ) {
    }
}

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

class ColourTransformer implements PropertyTransformerInterface
{
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $colours = [];

        foreach ($value as $colour) {
            $colours[] = new Colour($colour);
        }

        return $colours;
    }
}

$fooDto = new FooDto();
$fooDto->colours = ['red', 'green', 'blue'];

$mapper = AutoMapperBuilder::buildAutoMapper(
    propertyTransformers: [new ColourTransformer()]
);

return $mapper->map(
    $fooDto,
    Foo::class,
);
