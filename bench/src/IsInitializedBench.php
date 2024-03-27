<?php

use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

#[Iterations(30)]
class IsInitializedBench
{
    private \ReflectionClass $reflectionClass;
    private \ReflectionProperty $fooProperty;

    public function __construct()
    {
        $this->reflectionClass = new \ReflectionClass(Dummy::class);
        $this->fooProperty = $this->reflectionClass->getProperty('foo');
    }

    #[Revs(100_000)]
    #[Groups(['property'])]
    public function benchIsInitializedWithProperty()
    {
        $dummy = $this->reflectionClass->newInstanceWithoutConstructor();
        $property = $this->reflectionClass->getProperty('foo');
        $property->isInitialized($dummy);
    }

    #[Revs(100_000)]
    #[Groups(['property'])]
    public function benchIsInitializedWithoutProperty()
    {
        $dummy = $this->reflectionClass->newInstanceWithoutConstructor();
        $this->fooProperty->isInitialized($dummy);
    }

    #[Revs(100_000)]
    #[Groups(['property'])]
    public function benchIsset()
    {
        $dummy = $this->reflectionClass->newInstanceWithoutConstructor();
        isset($dummy->foo);
    }

    #[Revs(100_000)]
    #[Groups(['property'])]
    public function benchCatch()
    {
        $dummy = $this->reflectionClass->newInstanceWithoutConstructor();
        $isInitialized = true;
        try {
            $dummy->foo;
        } catch (\Error $e) {
            $isInitialized = false;
        }
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