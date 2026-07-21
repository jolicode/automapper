<?php

declare(strict_types=1);

namespace AutoMapper\Lazy;

/**
 * A collection whose elements are mapped lazily, as they are pulled from a source iterator.
 *
 * By default the collection is buffered: each element is mapped once, on first access, and
 * memoized so the collection can be iterated several times, rewound and counted. Peak memory
 * is therefore bounded by what has actually been traversed, up to the full collection.
 *
 * When constructed with `$buffered = false`, elements are streamed without memoization: the
 * collection never holds more than a single element, but it can only be iterated once (and it
 * cannot be counted or rewound). This is meant for known single-pass pipelines.
 *
 * Concurrent iteration (two loops over the same instance at once) is only safe under the
 * single-threaded execution model: only one iterator ever advances the shared source at a time.
 *
 * @template T
 *
 * @implements \IteratorAggregate<int|string, T>
 */
final class LazyCollection implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /** @var list<array{0: int|string, 1: T}> */
    private array $buffer = [];

    private bool $sourceExhausted = false;

    /** @var \Iterator<int|string, mixed> */
    private readonly \Iterator $source;

    /**
     * @param callable(mixed, int|string): T $mapItem
     * @param iterable<int|string, mixed>    $source
     */
    public function __construct(
        private $mapItem,
        iterable $source,
        private readonly bool $buffered = true,
    ) {
        $this->source = self::toIterator($source);
    }

    /**
     * @param iterable<int|string, mixed> $items
     *
     * @return \Iterator<int|string, mixed>
     */
    private static function toIterator(iterable $items): \Iterator
    {
        while ($items instanceof \IteratorAggregate) {
            $items = $items->getIterator();
        }

        if ($items instanceof \Iterator) {
            return $items;
        }

        return new \ArrayIterator(\is_array($items) ? $items : iterator_to_array($items));
    }

    public function getIterator(): \Generator
    {
        if (!$this->buffered) {
            yield from $this->stream();

            return;
        }

        $index = 0;

        while (true) {
            if ($index < \count($this->buffer)) {
                [$key, $value] = $this->buffer[$index++];

                yield $key => $value;

                continue;
            }

            if ($this->sourceExhausted || !$this->source->valid()) {
                $this->sourceExhausted = true;

                return;
            }

            $key = $this->source->key();
            $value = ($this->mapItem)($this->source->current(), $key);
            $this->source->next();

            $this->buffer[] = [$key, $value];

            yield $key => $value;

            ++$index;
        }
    }

    public function count(): int
    {
        // The source may know its length without mapping any value.
        if ([] === $this->buffer && !$this->sourceExhausted && $this->source instanceof \Countable) {
            return \count($this->source);
        }

        $count = 0;

        foreach ($this as $ignored) {
            ++$count;
        }

        return $count;
    }

    public function jsonSerialize(): mixed
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * @return \Generator<int|string, T>
     */
    private function stream(): \Generator
    {
        if ($this->sourceExhausted) {
            throw new \LogicException('This lazy collection was created without buffering and has already been consumed.');
        }

        $this->sourceExhausted = true;

        while ($this->source->valid()) {
            $key = $this->source->key();

            yield $key => ($this->mapItem)($this->source->current(), $key);

            $this->source->next();
        }
    }
}
