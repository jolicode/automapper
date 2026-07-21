<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer;

use AutoMapper\AutoMapperInterface;
use AutoMapper\Lazy\LazyCollection;
use AutoMapper\Lazy\LazyMap;
use AutoMapper\MapperContext;
use Symfony\Component\JsonStreamer\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * @implements StreamWriterInterface<MapperContextArray>
 *
 * @phpstan-import-type MapperContextArray from MapperContext
 */
final class JsonStreamWriter implements StreamWriterInterface
{
    public function __construct(
        private readonly AutoMapperInterface $mapper,
        /** @var StreamWriterInterface<array<string, mixed>> */
        private readonly StreamWriterInterface $fallbackStreamWriter,
    ) {
    }

    public function write(
        mixed $data,
        Type $type,
        array $options = [],
    ): \Traversable&\Stringable {
        if (
            $type instanceof ObjectType
            && \is_object($data)
            && $type->getClassName() === $data::class
        ) {
            $normalizedLazy = $this->mapper->map($data, 'array', [
                ...$options,
                'lazy_mapping' => true,
            ]);

            $chunks = $this->dataToChunks($normalizedLazy);

            return new /**
             * @implements \IteratorAggregate<int, string>
             */ class($chunks, ) implements \IteratorAggregate, \Stringable {
                /**
                 * @param \Traversable<int, string> $chunks
                 */
                public function __construct(
                    private \Traversable $chunks,
                ) {
                }

                public function getIterator(): \Traversable
                {
                    return $this->chunks;
                }

                public function __toString(): string
                {
                    $string = '';
                    foreach ($this->chunks as $chunk) {
                        $string .= $chunk;
                    }

                    return $string;
                }
            };
        }

        return $this->fallbackStreamWriter->write($data, $type, $options);
    }

    private function dataToChunks(mixed $data): \Generator
    {
        if ($data instanceof LazyMap) {
            yield from $this->lazyMapToChunks($data);
        } elseif ($data instanceof LazyCollection) {
            yield from $this->lazyCollectionToChunks($data);
        } else {
            yield json_encode($data);
        }
    }

    private function lazyMapToChunks(LazyMap $map): \Generator
    {
        yield '{';

        $values = iterator_to_array($map);
        $count = \count($values);
        $current = 0;

        foreach ($values as $key => $value) {
            yield '"' . $key . '":';
            yield from $this->dataToChunks($value);

            ++$current;
            if ($current < $count) {
                yield ',';
            }
        }

        yield '}';
    }

    /**
     * @param LazyCollection<array<string, mixed>> $collection
     */
    private function lazyCollectionToChunks(
        LazyCollection $collection,
    ): \Generator {
        yield '[';

        $first = true;

        foreach ($collection as $value) {
            if (!$first) {
                yield ',';
            }

            yield from $this->dataToChunks($value);

            $first = false;
        }

        yield ']';
    }
}
