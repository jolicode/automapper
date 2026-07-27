<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer;

use AutoMapper\AutoMapperInterface;
use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\MapperInterface;
use AutoMapper\Metadata\MetadataRegistry;
use Symfony\Component\JsonStreamer\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Streams an object (or a collection of objects) to JSON by delegating to the AutoMapper's
 * generated `json` mapper, whose `map()` yields the JSON chunk by chunk straight from the source
 * object — no intermediate array or lazy walk.
 *
 * @implements StreamWriterInterface<MapperContextArray>
 *
 * @phpstan-import-type MapperContextArray from \AutoMapper\MapperContext
 */
final class JsonStreamWriter implements StreamWriterInterface
{
    public function __construct(
        private readonly AutoMapperInterface $mapper,
        /** @var StreamWriterInterface<array<string, mixed>> */
        private readonly StreamWriterInterface $fallbackStreamWriter,
        /**
         * When set, only types having a mapper registered in this registry are written through the
         * AutoMapper; everything else is delegated to the underlying Symfony writer.
         */
        private readonly ?MetadataRegistry $onlyMetadataRegistry = null,
    ) {
    }

    public function write(
        mixed $data,
        Type $type,
        array $options = [],
    ): \Traversable&\Stringable {
        $unwrapped = $this->unwrap($type);

        if ($unwrapped instanceof CollectionType) {
            $className = $this->ownedClassName($this->unwrap($unwrapped->getCollectionValueType()));

            if ($className !== null && is_iterable($data) && $this->jsonMapper($className) !== null) {
                /** @var iterable<mixed> $data */
                $isList = $unwrapped->isList();

                return $this->wrap(fn (): \Generator => $this->collectionChunks($data, $className, $options, $isList));
            }
        }

        if (
            $unwrapped instanceof ObjectType
            && \is_object($data)
            && $unwrapped->getClassName() === $data::class
            && ($mapper = $this->jsonMapper($data::class)) !== null
        ) {
            return $this->wrap(static fn (): iterable => $mapper->map($data, $options) ?? []);
        }

        return $this->fallbackStreamWriter->write($data, $type, $options);
    }

    /**
     * Yield the JSON of a collection, one element at a time so a large collection is never held in
     * memory at once: a list is framed with `[` … `]`, a dict with `{` … `}` and its keys kept.
     *
     * @param iterable<mixed>    $data
     * @param class-string       $className
     * @param MapperContextArray $options
     *
     * @return \Generator<int, string>
     */
    private function collectionChunks(iterable $data, string $className, array $options, bool $isList): \Generator
    {
        $mapper = $this->jsonMapper($className);

        yield $isList ? '[' : '{';
        $sep = '';
        foreach ($data as $key => $item) {
            yield $isList ? $sep : $sep . $this->encodeKey($key) . ':';
            if (\is_object($item) && $item::class === $className && $mapper !== null) {
                yield from $mapper->map($item, $options) ?? [];
            } else {
                // JSON_THROW_ON_ERROR, like the Symfony writer: an unencodable value fails loudly
                // instead of silently emitting an empty (structurally invalid) chunk.
                yield json_encode($item, \JSON_THROW_ON_ERROR);
            }
            $sep = ',';
        }
        yield $isList ? ']' : '}';
    }

    /**
     * A JSON object key is always a string, whatever the PHP array key type is.
     */
    private function encodeKey(mixed $key): string
    {
        return json_encode((string) (\is_scalar($key) ? $key : ''), \JSON_THROW_ON_ERROR);
    }

    /**
     * Return the `json` mapper for the class, whose `map()` yields the JSON stream, or null when
     * the AutoMapper cannot provide one.
     *
     * @param class-string $className
     *
     * @return MapperInterface<object, iterable<int, string>>|null
     */
    private function jsonMapper(string $className): ?MapperInterface
    {
        if (!$this->mapper instanceof AutoMapperRegistryInterface) {
            return null;
        }

        // Registry aware: without a registered mapper for this class, let the Symfony writer do it.
        if (
            null !== $this->onlyMetadataRegistry
            && !$this->onlyMetadataRegistry->has($className, 'json', true)
            && !$this->onlyMetadataRegistry->has($className, 'array', true)
        ) {
            return null;
        }

        /** @var MapperInterface<object, iterable<int, string>> $mapper */
        $mapper = $this->mapper->getMapper($className, 'json');

        return $mapper;
    }

    /**
     * Wrap a chunk-generator factory so it can be consumed either streamed
     * (`getIterator`) or buffered (`__toString`). The factory is re-invoked per
     * consumption so the result stays re-iterable.
     *
     * @param \Closure(): iterable<int, string> $factory
     *
     * @return \Traversable<int, string>&\Stringable
     */
    private function wrap(\Closure $factory): \Traversable&\Stringable
    {
        return new /**
         * @implements \IteratorAggregate<int, string>
         */ class($factory) implements \IteratorAggregate, \Stringable {
            /**
             * @param \Closure(): iterable<int, string> $factory
             */
            public function __construct(
                private \Closure $factory,
            ) {
            }

            public function getIterator(): \Traversable
            {
                yield from ($this->factory)();
            }

            public function __toString(): string
            {
                $json = '';
                foreach (($this->factory)() as $chunk) {
                    $json .= $chunk;
                }

                return $json;
            }
        };
    }

    private function unwrap(Type $type): Type
    {
        while ($type instanceof NullableType || $type instanceof GenericType) {
            $type = $type->getWrappedType();
        }

        return $type;
    }

    /**
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
