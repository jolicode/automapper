<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer\Read;

use AutoMapper\Lazy\LazyMapInterface;

/**
 * A JSON object exposed as a lazy, read-only {@see \ArrayAccess} map.
 *
 * Because it implements {@see LazyMapInterface}, the AutoMapper treats it exactly like a source
 * `array`: the generated mapper reads each property through `offsetGet`/`offsetExists`, so no code
 * generation change is needed. Keys are located by scanning the object forward from where the last
 * scan stopped, recording every key's value offset along the way; a field is therefore parsed only
 * when it is actually read, and nested objects/lists stay lazy (see {@see JsonParser::parseValue()}).
 *
 * @implements LazyMapInterface<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 *
 * @internal
 */
final class LazyJsonObject implements LazyMapInterface, \IteratorAggregate, \JsonSerializable
{
    /**
     * Discovered keys, in document order, so iteration and re-lookup are stable.
     *
     * @var list<string>
     */
    private array $keys = [];

    /** @var array<string, int> key => offset of its value */
    private array $offsets = [];

    /** Offset to resume scanning key/value pairs from. */
    private int $cursor;

    private bool $complete = false;

    public function __construct(
        private readonly JsonBuffer $buffer,
        int $start,
    ) {
        $this->cursor = $start + 1; // move past the opening brace
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->locate((string) $offset) !== null;
    }

    public function offsetGet(mixed $offset): mixed
    {
        $valueOffset = $this->locate((string) $offset);

        if ($valueOffset === null) {
            return null;
        }

        return JsonParser::parseValue($this->buffer, $valueOffset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('A lazily decoded JSON object is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('A lazily decoded JSON object is read-only.');
    }

    /**
     * @return \Generator<string, mixed>
     */
    public function getIterator(): \Generator
    {
        $index = 0;

        while (true) {
            if ($index < \count($this->keys)) {
                $key = $this->keys[$index++];

                yield $key => JsonParser::parseValue($this->buffer, $this->offsets[$key]);

                continue;
            }

            if (!$this->scanNext()) {
                return;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return iterator_to_array($this);
    }

    /**
     * Offset of the value for $key, scanning further into the object if needed, or null when the
     * key is absent.
     */
    private function locate(string $key): ?int
    {
        if (\array_key_exists($key, $this->offsets)) {
            return $this->offsets[$key];
        }

        while (!$this->complete) {
            if ($this->scanNext() && $this->keys[\count($this->keys) - 1] === $key) {
                return $this->offsets[$key];
            }
        }

        return null;
    }

    /**
     * Read the next key/value pair, recording it, and return whether one was found (false at the
     * object's closing brace or at the end of the input).
     */
    private function scanNext(): bool
    {
        if ($this->complete) {
            return false;
        }

        $pos = JsonParser::skipWhitespace($this->buffer, $this->cursor);
        $c = $this->buffer->byteAt($pos);

        if ($c === ',') {
            $pos = JsonParser::skipWhitespace($this->buffer, $pos + 1);
            $c = $this->buffer->byteAt($pos);
        }

        if ($c !== '"') { // closing brace, or malformed/exhausted input
            $this->complete = true;

            return false;
        }

        [$key, $afterKey] = $this->readKey($pos);
        $valueOffset = JsonParser::skipWhitespace($this->buffer, $afterKey + 1); // move past ':'

        $this->keys[] = $key;
        $this->offsets[$key] = $valueOffset;
        $this->cursor = JsonParser::skipValue($this->buffer, $valueOffset);

        return true;
    }

    /**
     * @return array{string, int} the decoded key and the offset of the byte after it (the `:`)
     */
    private function readKey(int $pos): array
    {
        $end = JsonParser::skipValue($this->buffer, $pos);
        /** @var string $key */
        $key = json_decode($this->buffer->slice($pos, $end - $pos), true, 512, \JSON_THROW_ON_ERROR);

        return [$key, JsonParser::skipWhitespace($this->buffer, $end)];
    }
}
