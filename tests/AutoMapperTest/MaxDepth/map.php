<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\MaxDepth;

use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\Serializer\Annotation\MaxDepth;

class FooMaxDepth
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var FooMaxDepth|null
     */
    #[MaxDepth(2)]
    private $child;

    public function __construct(int $id, ?self $child = null)
    {
        $this->id = $id;
        $this->child = $child;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getChild(): ?self
    {
        return $this->child;
    }
}

$foo = new FooMaxDepth(0, new FooMaxDepth(1, new FooMaxDepth(2, new FooMaxDepth(3, new FooMaxDepth(4)))));

return AutoMapperBuilder::buildAutoMapper()->map($foo, 'array');
