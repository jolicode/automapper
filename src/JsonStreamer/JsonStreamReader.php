<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer;

use AutoMapper\AutoMapperInterface;
use AutoMapper\JsonStreamer\Read\JsonDecoder;
use AutoMapper\JsonStreamer\Read\LazyJsonList;
use AutoMapper\JsonStreamer\Read\LazyJsonObject;
use AutoMapper\Lazy\LazyCollection;
use AutoMapper\MapperContext;
use Symfony\Component\JsonStreamer\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Reads a JSON stream into objects, delegating the object construction to the AutoMapper.
 *
 * Instead of asking the underlying Symfony reader to instantiate the target objects
 * (which bypasses the AutoMapper entirely), we ask it to decode the JSON into its plain
 * array shape and let the AutoMapper map that shape into objects. This keeps all of the
 * AutoMapper features (renames, transformers, discriminators, constructors, ...) available
 * on the way in.
 *
 * For a collection of objects the JSON is decoded lazily (element by element) so a large
 * top-level array never has to be held in memory at once.
 *
 * @implements StreamReaderInterface<MapperContextArray>
 *
 * @phpstan-import-type MapperContextArray from MapperContext
 */
final class JsonStreamReader implements StreamReaderInterface
{
    public function __construct(
        private readonly AutoMapperInterface $mapper,
        /** @var StreamReaderInterface<array<string, mixed>> */
        private readonly StreamReaderInterface $fallbackStreamReader,
    ) {
    }

    public function read($input, Type $type, array $options = []): mixed
    {
        $unwrapped = $this->unwrap($type);

        if ($unwrapped instanceof CollectionType) {
            $className = $this->ownedClassName($this->unwrap($unwrapped->getCollectionValueType()));

            if ($className !== null) {
                $buffered = !($options[MapperContext::STREAM] ?? false);

                // Decode lazily: each element is exposed as an array-like source (LazyJsonObject) so
                // the AutoMapper reads only the fields it maps. In streaming mode the decoder frees
                // each element once iterated past, so a large top-level array stays memory-bounded;
                // LazyCollection then maps each element as it is pulled.
                if (function_exists('json_stream_decode')) {
                    // Only release consumed content (transient) in streaming mode: a buffered
                    // collection must stay re-readable, and releasing would also break the mapper's
                    // out-of-order field reads on a large document.
                    $decoded = json_stream_decode($input, $buffered ? 0 : JSON_STREAM_TRANSIENT);
                } else {
                    $decoded = JsonDecoder::decode($input, streaming: !$buffered);

                    if (!$decoded instanceof LazyJsonList && !$decoded instanceof LazyJsonObject) {
                        // The JSON is not a list or an object (null, scalar): nothing to iterate.
                        return $decoded;
                    }
                }

                return new LazyCollection(
                    fn (mixed $rawItem): mixed => \is_array($rawItem) || \is_object($rawItem)
                        ? $this->mapper->map($rawItem, $className, $options)
                        : $rawItem,
                    $decoded,
                    $buffered,
                );
            }
        }

        $className = $this->ownedClassName($unwrapped);

        if ($className !== null) {
            // Decode lazily: the object is exposed as an array-like source (LazyJsonObject) so the
            // AutoMapper reads only the fields it maps, straight from the stream, and nested
            // objects/lists stay lazy — no intermediate array is materialized.
            if (function_exists('json_stream_decode')) {
                $decoded = json_stream_decode($input);
            } else {
                $decoded = JsonDecoder::decode($input, streaming: !$buffered);

                if (!$decoded instanceof LazyJsonObject) {
                    // The JSON is not an object (null, scalar, list): nothing for the AutoMapper to
                    // hydrate, hand the decoded value back as-is.
                    return $decoded;
                }
            }

            return $this->mapper->map($decoded, $className, $options);
        }

        return $this->fallbackStreamReader->read($input, $type, $options);
    }

    /**
     * Unwrap nullable and generic wrappers, but never a collection (whose wrapped type
     * is the underlying `array`/`iterable` builtin, not the value we care about).
     */
    private function unwrap(Type $type): Type
    {
        while ($type instanceof NullableType || $type instanceof GenericType) {
            $type = $type->getWrappedType();
        }

        return $type;
    }

    /**
     * Return the class name when the AutoMapper should own its construction, or null when
     * the underlying reader is a better fit (scalars, enums, and value objects handled by the
     * Symfony value transformers).
     *
     * @return class-string|null
     */
    private function ownedClassName(Type $type): ?string
    {
        if (!$type instanceof ObjectType) {
            return null;
        }

        /** @var class-string $className */
        $className = $type->getClassName();

        if (
            is_a($className, \DateTimeInterface::class, true)
            || is_a($className, \DateInterval::class, true)
            || is_a($className, \DateTimeZone::class, true)
        ) {
            return null;
        }

        return $className;
    }
}
