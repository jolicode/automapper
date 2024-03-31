<?php

use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[Iterations(30)]
class CreateObjectBench
{
    private \ReflectionClass $reflectionClass;
    private \ReflectionProperty $fooProperty;
    private Dummy $dummy;

    public function __construct()
    {
        $this->reflectionClass = new \ReflectionClass(Dummy::class);
        $this->fooProperty = $this->reflectionClass->getProperty('foo');
        $this->dummy = $this->reflectionClass->newInstanceWithoutConstructor();
    }

    #[Revs(100_000)]
    #[Groups(['construct'])]
    public function benchWithNew()
    {
        $object = new Dummy(foo: 'foo', bar: 'bar');
    }

    #[Revs(100_000)]
    #[Groups(['construct'])]
    public function benchWithActualConstructCall()
    {
        $object = clone $this->dummy;
        $object->__construct(foo: 'foo', bar: 'bar');
    }

    #[Revs(100_000)]
    #[Groups(['construct'])]
    public function benchWithReflectionAndConstruct()
    {
        $object = $this->reflectionClass->newInstanceWithoutConstructor();
        $object->__construct(foo: 'foo', bar: 'bar');
    }

    #[Revs(100_000)]
    #[Groups(['construct'])]
    public function benchWithReflectionArgs()
    {
        $this->reflectionClass->newInstanceArgs(['foo' => 'foo', 'bar' => 'bar']);
    }

    #[Revs(100_000)]
    #[Groups(['construct'])]
    public function benchWithReflection()
    {
        $this->reflectionClass->newInstance(foo: 'foo', bar: 'bar');
    }

    #[Revs(100_000)]
    #[Groups(['construct'])]
    public function benchWithCloneAndCallUserFunc()
    {
        $object = clone $this->dummy;
        \call_user_func([$object, '__construct'], 'foo', 'bar');
    }

    #[Revs(100_000)]
    #[Groups(['construct'])]
    public function benchWithCloneAndCallUserFuncArray()
    {
        $object = clone $this->dummy;
        \call_user_func_array([$object, '__construct'], ['foo' => 'foo', 'bar' => 'bar']);
    }
}

class Dummy
{
    public function __construct(
        public string $foo,
        public string $bar,
    ) {
    }
}