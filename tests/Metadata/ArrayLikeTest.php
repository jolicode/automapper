<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Metadata;

use AutoMapper\Attribute\Mapper;
use AutoMapper\Tests\AutoMapperBuilder;
use PHPUnit\Framework\TestCase;

/**
 * A class marked with `#[Mapper(arrayLike: true)]` is read (and written) as a keyed array shape
 * instead of a typed object, whatever its own properties are.
 *
 * @covers \AutoMapper\Attribute\Mapper
 * @covers \AutoMapper\Metadata\MapperMetadata
 */
class ArrayLikeTest extends TestCase
{
    public function testArrayLikeSourceIsReadByKey(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'ArrayLike_');

        $target = $autoMapper->map(new ArrayLikeBag(['id' => 7, 'name' => 'seven']), ArrayLikeTarget::class);

        self::assertInstanceOf(ArrayLikeTarget::class, $target);
        self::assertSame(7, $target->id);
        self::assertSame('seven', $target->name);
    }

    public function testMissingKeyIsSkippedInsteadOfMappedAsNull(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'ArrayLike_');

        $target = $autoMapper->map(new ArrayLikeBag(['id' => 3]), ArrayLikeTarget::class);

        self::assertSame(3, $target->id);
        // the key is absent from the bag: the target keeps its default value
        self::assertSame('', $target->name);
    }

    public function testWithoutTheAttributeTheClassIsMappedAsAnObject(): void
    {
        $autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'ArrayLike_');

        // the same shape without the attribute exposes no readable property, so nothing is mapped
        $target = $autoMapper->map(new PlainBag(['id' => 7, 'name' => 'seven']), ArrayLikeTarget::class);

        self::assertSame(0, $target->id);
        self::assertSame('', $target->name);
    }
}

/**
 * @implements \ArrayAccess<string, mixed>
 */
#[Mapper(arrayLike: true)]
class ArrayLikeBag implements \ArrayAccess
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data = [],
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}

/**
 * @implements \ArrayAccess<string, mixed>
 */
class PlainBag implements \ArrayAccess
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data = [],
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}

class ArrayLikeTarget
{
    public int $id = 0;
    public string $name = '';
}
