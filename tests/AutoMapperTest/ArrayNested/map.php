<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ArrayNested;

use AutoMapper\Tests\AutoMapperBuilder;

class ClassContainer
{
    public ?array $endpoints = null;
}

class TestSubTarget
{
    public function __construct(
        private ?string $endpoint = null,
        private ?array $params = null,
    ) {
    }
}

class TestTarget
{
    public function __construct(
        private ?TestSubTarget $endpoints = null,
    ) {
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $classContainer = new ClassContainer();
    $classContainer->endpoints = [
        'endpoint' => 'https://example.com',
        'params' => ['param1', 'param2'],
    ];

    yield 'array' => $autoMapper->map($classContainer, TestTarget::class);
})();
