<?php

declare(strict_types=1);

namespace AutoMapper\Lazy;

/**
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 */
final class LazyMap implements \ArrayAccess, \JsonSerializable, \IteratorAggregate
{
    /** @var array<mixed> */
    private mixed $mappedValue = [];

    private bool $initialized = false;

    /**
     * @param (callable(array<mixed>): mixed) $mapper
     */
    public function __construct(
        private $mapper,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        $this->initialize();

        return isset($this->mappedValue[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->initialize();

        if (!isset($this->mappedValue[$offset])) {
            return null;
        }

        return $this->mappedValue[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->initialize();

        $this->mappedValue[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->initialize();

        unset($this->mappedValue[$offset]);
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        ($this->mapper)($this->mappedValue);
    }

    public function jsonSerialize(): mixed
    {
        $this->initialize();

        return $this->mappedValue;
    }

    public function getIterator(): \Traversable
    {
        $this->initialize();

        return new \ArrayIterator($this->mappedValue);
    }
}
