<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Provider;

use AutoMapper\Attribute\MapProvider;
use AutoMapper\Provider\EarlyReturn;
use AutoMapper\Provider\ProviderInterface;
use AutoMapper\Tests\AutoMapperBuilder;

final readonly class CustomProvider implements ProviderInterface
{
    public function __construct(
        private object|array|null $value
    ) {
    }

    public function provide(string $targetType, mixed $source, array $context): object|array|null
    {
        return $this->value;
    }
}

#[MapProvider(provider: CustomProvider::class, source: 'array')]
class MyObject
{
    public string $foo;

    public string $bar;
}

return (function () {
    $myObject = new MyObject();
    $myObject->foo = 'bar';

    $autoMapper = AutoMapperBuilder::buildAutoMapper(providers: [new CustomProvider($myObject)]);

    yield 'bar' => $autoMapper->map(['bar' => 'foo'], MyObject::class);
    yield 'bar-foo' => $autoMapper->map(['bar' => 'foo', 'foo' => 'foo'], MyObject::class);

    $myObject = new MyObject();
    $myObject->foo = 'bar';
    $myObject->bar = 'foo';

    $autoMapper = AutoMapperBuilder::buildAutoMapper(providers: [new CustomProvider(new EarlyReturn($myObject))]);

    yield 'early-return' => $autoMapper->map(['bar' => 'bar', 'foo' => 'foo'], MyObject::class);
})();
