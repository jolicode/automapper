<?php

declare(strict_types=1);

namespace AutoMapper\Lazy;

use AutoMapper\Attribute\Mapper;

/**
 * A map whose values are produced on first access, used as the target of a lazy `array` mapping.
 *
 * It is read by key like a plain `array` source, hence the `arrayLike` marker.
 *
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 */
#[Mapper(arrayLike: true)]
final class LazyMap implements \ArrayAccess, \JsonSerializable, \IteratorAggregate
{
    /** @var array<string, mixed> */
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

    /**
     * @param string $offset
     */
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
        $this->initialized = true;
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
