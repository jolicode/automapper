<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer\Read;

/**
 * A JSON array exposed as a lazy, re-iterable list.
 *
 * Iterating decodes one element at a time straight from the buffer, so a large array of objects is
 * never materialized at once and each element's own laziness is preserved (an object element is a
 * {@see LazyJsonObject}).
 *
 * By default the node is stateless over the buffer, so it can be iterated more than once — useful
 * when the AutoMapper both counts and maps a collection. In `$streaming` mode it instead frees each
 * element from the buffer once it has advanced past it, so a huge top-level array is bounded to one
 * element's worth of bytes; the trade-off is that it can then be iterated only once and each
 * element must be fully consumed before the next is pulled.
 *
 * @implements \IteratorAggregate<int, mixed>
 *
 * @internal
 */
final class LazyJsonList implements \IteratorAggregate, \JsonSerializable
{
    public function __construct(
        private readonly JsonBuffer $buffer,
        private readonly int $start,
        private readonly bool $streaming = false,
    ) {
    }

    /**
     * @return \Generator<int, mixed>
     */
    public function getIterator(): \Generator
    {
        $pos = JsonParser::skipWhitespace($this->buffer, $this->start + 1); // move past '['

        if ($this->buffer->byteAt($pos) === ']') {
            return;
        }

        $index = 0;

        while (true) {
            if ($this->streaming) {
                // The previous element has been fully consumed by now: free it.
                $this->buffer->discardBefore($pos);
            }

            yield $index++ => JsonParser::parseValue($this->buffer, $pos);

            $pos = JsonParser::skipWhitespace($this->buffer, JsonParser::skipValue($this->buffer, $pos));

            if ($this->buffer->byteAt($pos) !== ',') {
                return; // closing bracket or exhausted input
            }

            $pos = JsonParser::skipWhitespace($this->buffer, $pos + 1);
        }
    }

    /**
     * @return array<int, mixed>
     */
    public function jsonSerialize(): array
    {
        return iterator_to_array($this);
    }
}
