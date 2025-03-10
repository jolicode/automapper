<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Paramters;

use AutoMapper\Tests\AutoMapperBuilder;

class Paramters
{
    /** @var array<string, string> */
    private $parameters;

    /**
     * @param array<string, string> $parameters
     */
    public function __construct(array $parameters)
    {
        $this->setParameters($parameters);
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $arguments = ['foo', 'bar', 'baz'];
    $parameters = new Paramters($arguments);

    yield 'int' => $autoMapper->map($parameters, 'array');

    $arguments = ['foo', 'azerty' => 'bar', 'baz'];
    $parameters = new Paramters($arguments);

    yield 'mixed' => $autoMapper->map($parameters, 'array');

    $arguments = ['foo' => 'azerty', 'bar' => 'qwerty', 'baz' => 'dvorak'];
    $parameters = new Paramters($arguments);

    yield 'string' => $autoMapper->map($parameters, 'array');
})();
