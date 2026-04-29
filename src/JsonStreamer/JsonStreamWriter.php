<?php

namespace AutoMapper\JsonStreamer;

use Symfony\Component\JsonStreamer\StreamWriterInterface;
use AutoMapper\Lazy\LazyCollection;
use AutoMapper\Lazy\LazyMap;
use AutoMapper\AutoMapperInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class JsonStreamWriter implements StreamWriterInterface
{
    public function __construct(
        private readonly AutoMapperInterface $mapper,
        private readonly StreamWriterInterface $fallbackStreamWriter,
    ) {}

    public function write(
        mixed $data,
        Type $type,
        array $options = [],
    ): \Traversable&\Stringable {
        if (
            $type instanceof ObjectType &&
            $type->getClassName() === get_class($data)
        ) {
            $normalizedLazy = $this->mapper->map($data, "array", [
                ...$options,
                "lazy_mapping" => true,
            ]);

            $chunks = $this->dataToChunks($normalizedLazy);

            return new /**
             * @implements \IteratorAggregate<int, string>
             */ class (
                $chunks,
            ) implements \IteratorAggregate, \Stringable {
                /**
                 * @param \Traversable<string> $chunks
                 */
                public function __construct(private \Traversable $chunks) {}

                public function getIterator(): \Traversable
                {
                    return $this->chunks;
                }

                public function __toString(): string
                {
                    $string = "";
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
            yield from $this->lazyMapToChunks($data, []);
        } elseif ($data instanceof LazyCollection) {
            yield from $this->lazyCollectionToChunks($data, []);
        } else {
            yield json_encode($data);
        }
    }

    private function lazyMapToChunks(LazyMap $map): \Generator
    {
        yield "{";

        $values = iterator_to_array($map);
        $count = count($values);
        $current = 0;

        foreach ($values as $key => $value) {
            yield '"' . $key . '"' . ":";
            yield from $this->dataToChunks($value);

            $current++;
            if ($current < $count) {
                yield ",";
            }
        }

        yield "}";
    }

    private function lazyCollectionToChunks(
        LazyCollection $collection,
    ): \Generator {
        yield "[";

        $collection->rewind();
        $first = true;

        while ($collection->valid()) {
            if (!$first) {
                yield ",";
            }

            $value = $collection->current();
            yield from $this->dataToChunks($value);

            $collection->next();
            $first = false;
        }

        yield "]";
    }
}
