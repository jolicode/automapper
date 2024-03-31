<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Normalizer;

class DeepObjectPopulateChildDummy
{
    public $foo;

    public $bar;

    // needed to have GetSetNormalizer consider this class as supported
    public function getFoo()
    {
        return $this->foo;
    }

    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }
}
