<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer\Read;

/**
 * A seekable byte buffer over a JSON input.
 *
 * The lazy decoder addresses the input by absolute byte offset and needs to be able to look at a
 * byte again after having moved past it (JSON key order does not match the mapper's property
 * order, so a field is often located only after later ones have been scanned). A raw stream is not
 * necessarily seekable, so we never seek it: bytes are pulled from it once, appended to an internal
 * string, and random access happens against that string.
 *
 * For a plain string input the whole content is available immediately. For a resource, bytes are
 * read on demand, chunk by chunk, up to the highest offset requested so far.
 *
 * Offsets are absolute and stable for the lifetime of the buffer, even after {@see discardBefore()}
 * frees already-consumed leading bytes (so a large top-level collection streamed element by element
 * never holds more than one element's worth of bytes). Accessing a discarded offset is a bug and
 * throws.
 *
 * @internal
 */
final class JsonBuffer
{
    private string $data = '';

    private bool $eof = false;

    /** Absolute offset of the first byte still held in $data (bytes before it were discarded). */
    private int $base = 0;

    /** @var resource|null */
    private $stream;

    /**
     * @param resource|string $input
     * @param int<1, max>     $chunkSize
     */
    public function __construct(
        mixed $input,
        private readonly int $chunkSize = 8192,
    ) {
        if (\is_string($input)) {
            $this->data = $input;
            $this->eof = true;
            $this->stream = null;

            return;
        }

        if (!\is_resource($input)) {
            throw new \InvalidArgumentException(\sprintf('The JSON buffer expects a string or a stream resource, "%s" given.', get_debug_type($input)));
        }

        $this->stream = $input;
    }

    /**
     * The byte at $offset, or null once the input is exhausted at that offset.
     */
    public function byteAt(int $offset): ?string
    {
        $this->fillTo($offset);
        $local = $this->localize($offset);

        return $local < \strlen($this->data) ? $this->data[$local] : null;
    }

    /**
     * The $length bytes starting at $offset (possibly shorter at the end of the input).
     */
    public function slice(int $offset, int $length): string
    {
        $this->fillTo($offset + $length - 1);

        return substr($this->data, $this->localize($offset), $length);
    }

    /**
     * Free every byte before $offset. Later access below $offset is no longer possible; callers
     * must only discard past data they are certain no live node will read again (the streaming
     * top-level list discards each element once it has advanced past it).
     */
    public function discardBefore(int $offset): void
    {
        if ($offset <= $this->base) {
            return;
        }

        $this->fillTo($offset - 1);
        $drop = min($offset - $this->base, \strlen($this->data));
        $this->data = substr($this->data, $drop);
        $this->base += $drop;
    }

    /**
     * Translate an absolute offset into an index inside $data, failing loudly on discarded bytes.
     */
    private function localize(int $offset): int
    {
        if ($offset < $this->base) {
            throw new \LogicException(\sprintf('Byte at offset %d was already discarded from the JSON buffer.', $offset));
        }

        return $offset - $this->base;
    }

    /**
     * Pull bytes from the stream until $offset is available or the input is exhausted.
     */
    private function fillTo(int $offset): void
    {
        while (!$this->eof && $offset - $this->base >= \strlen($this->data)) {
            if (!\is_resource($this->stream)) {
                $this->eof = true;

                break;
            }

            $chunk = fread($this->stream, $this->chunkSize);

            if ($chunk === false || $chunk === '') {
                $this->eof = true;

                break;
            }

            $this->data .= $chunk;
        }
    }
}
