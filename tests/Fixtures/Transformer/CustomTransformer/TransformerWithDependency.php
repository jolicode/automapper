<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

use AutoMapper\Tests\Fixtures\CityFoo;
use AutoMapper\Transformer\CustomTransformer\CustomPropertyTransformerInterface;

final readonly class TransformerWithDependency implements CustomPropertyTransformerInterface
{
    public function __construct(private FooDependency $fooDependency)
    {
    }

    public function supports(string $source, string $target, string $propertyName): bool
    {
        return $source === CityFoo::class && $target === 'array' && $propertyName === 'name';
    }

    public function transform(mixed $source): string
    {
        return $this->fooDependency->getBar();
    }
}
