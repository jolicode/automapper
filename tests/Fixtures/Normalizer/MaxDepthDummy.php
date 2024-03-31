<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Normalizer;

use Symfony\Component\Serializer\Attribute\MaxDepth;

class MaxDepthDummy
{
    #[MaxDepth(2)]
    public $foo;

    public $bar;

    /**
     * @var self
     */
    public $child;

    #[MaxDepth(3)]
    public function getBar()
    {
        return $this->bar;
    }

    public function getChild()
    {
        return $this->child;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}
