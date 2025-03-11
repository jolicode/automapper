<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\SkipNullValues;

use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;

class Input
{
    /**
     * @var string|null
     */
    public $name;
}

class Entity
{
    /**
     * @var string
     */
    private $name;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

$entity = new Entity();
$entity->setName('foobar');
$input = new Input();

/** @var Entity $entity */
return AutoMapperBuilder::buildAutoMapper()->map($input, $entity, [MapperContext::SKIP_NULL_VALUES => true]);
