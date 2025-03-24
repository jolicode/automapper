<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Ignore;

use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\Serializer\Annotation\Ignore;

class FooIgnore
{
    /**
     * @var int
     */
    #[Ignore]
    public $id;

    public function getId(): int
    {
        return $this->id;
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $foo = new FooIgnore();
    $foo->id = 5;
    yield 'ignore-in-source' => $autoMapper->map($foo, 'array');

    $foo = ['id' => 5];
    yield 'ignore-in-target' => $autoMapper->map($foo, FooIgnore::class);
})();
