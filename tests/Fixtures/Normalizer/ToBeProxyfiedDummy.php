<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Normalizer;

class ToBeProxyfiedDummy
{
    private $foo;

    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}
