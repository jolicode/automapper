<?php

declare(strict_types=1);

namespace AutoMapper\Lazy;

use AutoMapper\MapperInterface;

/**
 * @template S of object|array
 * @template T of object|array
 *
 * @implements \Iterator<int|string, T>
 *
 * @phpstan-import-type MapperContextArray from \AutoMapper\MapperContext
 */
final class LazyCollection implements \Countable, \Iterator
{
    /** @var array<int|string, T|null> */
    private array $buffer = [];

    private bool $valid = true;

    /**
     * @param MapperInterface<S, T>   $mapper
     * @param iterable<int|string, S> $sourceValues
     * @param MapperContextArray      $context
     */
    public function __construct(
        private readonly MapperInterface $mapper,
        private iterable $sourceValues,
        private array $context = [],
    ) {
    }

    public function current(): mixed
    {
        /** @var T */
        return current($this->buffer);
    }

    public function next(): void
    {
        if (false === next($this->buffer)) {
            return;
        }

        // Get the next value from the source values
        if (false !== next($this->sourceValues)) {
            /** @var S $current */
            $current = current($this->sourceValues);
            /** @var int|string|null */
            $key = key($this->sourceValues);

            if (null !== $key) {
                $this->buffer[$key] = $this->mapper->map($current, $this->context);
            } else {
                $this->buffer[] = $this->mapper->map($current, $this->context);
            }

            return;
        }

        $this->valid = false;
    }

    public function key(): mixed
    {
        /** @var int|string */
        return key($this->buffer);
    }

    public function valid(): bool
    {
        return $this->valid;
    }

    public function rewind(): void
    {
        reset($this->buffer);
    }

    public function count(): int
    {
        if (\is_array($this->sourceValues)) {
            return \count($this->sourceValues);
        }

        if ($this->sourceValues instanceof \Countable) {
            return \count($this->sourceValues);
        }

        return iterator_count($this->sourceValues);
    }
}
