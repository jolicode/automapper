<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\MapperAttributePriority;

use AutoMapper\Attribute\Mapper;
use AutoMapper\ConstructorStrategy;
use AutoMapper\Tests\AutoMapperBuilder;

// same example as docs/mapping/mapper-attribute.md: the highest priority #[Mapper] must win
#[Mapper(source: EntityDto::class, constructorStrategy: ConstructorStrategy::NEVER, priority: 2)]
class Entity
{
    public string $foo;

    public function __construct(string $foo = 'from constructor')
    {
        $this->foo = $foo . ' (constructor used)';
    }
}

#[Mapper(constructorStrategy: ConstructorStrategy::ALWAYS, priority: 1)]
class EntityDto
{
    public string $foo = 'bar';
}

return AutoMapperBuilder::buildAutoMapper()->map(new EntityDto(), Entity::class);
