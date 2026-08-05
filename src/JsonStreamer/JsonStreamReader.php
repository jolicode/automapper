<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer;

use AutoMapper\AutoMapperInterface;
use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\Lazy\LazyCollection;
use AutoMapper\MapperContext;
use AutoMapper\MapperInterface;
use AutoMapper\Metadata\MetadataRegistry;
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
        private readonly AutoMapperInterface&AutoMapperRegistryInterface $mapper,
        /** @var StreamReaderInterface<array<string, mixed>> */
        private readonly StreamReaderInterface $fallbackStreamReader,
        /**
         * When set, only types having a mapper registered in this registry are read through the
         * AutoMapper; everything else is delegated to the underlying Symfony reader.
         */
        private readonly ?MetadataRegistry $onlyMetadataRegistry = null,
    ) {
    }

    public function read($input, Type $type, array $options = []): mixed
    {
        $unwrapped = $this->unwrap($type);

        if ($unwrapped instanceof CollectionType) {
            $className = $this->ownedClassName($this->unwrap($unwrapped->getCollectionValueType()));

            if ($className !== null) {
                $buffered = !($options[MapperContext::STREAM] ?? false);
                $decoded = json_stream_decode($input, $buffered ? 0 : JSON_STREAM_TRANSIENT);

                $mapper = $this->jsonMapper($className);

                return new LazyCollection(
                    // a decoded container is always a document object, anything else is a scalar
                    static fn (mixed $rawItem): mixed => \is_object($rawItem)
                        ? $mapper->map($rawItem, $options)
                        : $rawItem,
                    $decoded,
                    $buffered,
                );
            }
        }

        $className = $this->ownedClassName($unwrapped);

        if ($className !== null) {
            $buffered = !($options[MapperContext::STREAM] ?? false);
            $decoded = json_stream_decode($input, $buffered ? 0 : JSON_STREAM_TRANSIENT);

            return $this->jsonMapper($className)->map($decoded, $options);
        }

        return $this->fallbackStreamReader->read($input, $type, $options);
    }

    /**
     * The `json` → class mapper, reading straight from the decoded document.
     *
     * @param class-string $className
     *
     * @return MapperInterface<object, object>
     */
    private function jsonMapper(string $className): MapperInterface
    {
        /** @var MapperInterface<object, object> */
        return $this->mapper->getMapper('json', $className);
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
     * Symfony value transformers, or types without a registered mapper).
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

        // Registry aware: without a registered mapper for this class, let the Symfony reader do it.
        if (null !== $this->onlyMetadataRegistry && !$this->onlyMetadataRegistry->has('json', $className, true)) {
            return null;
        }

        return $className;
    }
}
