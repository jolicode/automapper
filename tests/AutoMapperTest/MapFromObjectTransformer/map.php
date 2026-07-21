<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\MapFromObjectTransformer;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Tests\AutoMapperBuilder;

final class PriceFormatter
{
    public function __invoke(mixed $value, object|array $source, array $context): string
    {
        return number_format($value / 100, 2) . ' €';
    }
}

class Order
{
    public int $price = 12345;
}

class OrderDto
{
    #[MapFrom(source: Order::class, transformer: new PriceFormatter())]
    public string $price;
}

return AutoMapperBuilder::buildAutoMapper()->map(new Order(), OrderDto::class);
