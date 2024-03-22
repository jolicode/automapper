<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Normalizer;

use AutoMapper\AutoMapperInterface;
use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\MapperContext;
use AutoMapper\MapperInterface;
use AutoMapper\Normalizer\AutoMapperNormalizer;
use AutoMapper\Tests\AutoMapperBaseTest;
use AutoMapper\Tests\Fixtures;
use AutoMapper\Tests\Fixtures\ObjectWithDateTime;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class AutoMapperNormalizerTest extends AutoMapperBaseTest
{
    protected AutoMapperNormalizer $normalizer;

    protected function setUp(): void
    {
        if (!interface_exists(NormalizerInterface::class)) {
            self::markTestSkipped('Symfony Serializer is required to run this test.');
        }

        parent::setUp();
        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true);
        $this->normalizer = new AutoMapperNormalizer($this->autoMapper);
    }

    public function testNormalize(): void
    {
        $object = new Fixtures\User(1, 'Jack', 37);
        $expected = ['id' => 1, 'name' => 'Jack', 'age' => 37];

        $normalized = $this->normalizer->normalize($object);
        self::assertIsArray($normalized);
        self::assertEquals($expected['id'], $normalized['id']);
        self::assertEquals($expected['name'], $normalized['name']);
        self::assertEquals($expected['age'], $normalized['age']);
    }

    public function testDenormalize(): void
    {
        $source = ['id' => 1, 'name' => 'Jack', 'age' => 37];

        /** @var Fixtures\User $denormalized */
        $denormalized = $this->normalizer->denormalize($source, Fixtures\User::class);
        self::assertInstanceOf(Fixtures\User::class, $denormalized);
        self::assertEquals($source['id'], $denormalized->getId());
        self::assertEquals($source['name'], $denormalized->name);
        self::assertEquals($source['age'], $denormalized->age);
    }

    public function testSupportsNormalization(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(['foo']));
        self::assertFalse($this->normalizer->supportsNormalization('{"foo":1}'));

        $iterator = new class() implements \IteratorAggregate {
            public function getIterator(): \Traversable
            {
                yield 'id' => 1;
                yield 'name' => 'Jack';
                yield 'age' => 37;
            }
        };

        self::assertFalse($this->normalizer->supportsNormalization($iterator));

        $object = new Fixtures\User(1, 'Jack', 37);
        self::assertTrue($this->normalizer->supportsNormalization($object));

        $stdClass = new \stdClass();
        $stdClass->id = 1;
        $stdClass->name = 'Jack';
        $stdClass->age = 37;
        self::assertFalse($this->normalizer->supportsNormalization($stdClass));
    }

    public function testSupportsDenormalization(): void
    {
        self::assertFalse($this->normalizer->supportsDenormalization(['foo' => 1], 'array'));
        self::assertFalse($this->normalizer->supportsDenormalization(['foo' => 1], 'json'));

        $user = ['id' => 1, 'name' => 'Jack', 'age' => 37];
        self::assertTrue($this->normalizer->supportsDenormalization($user, Fixtures\User::class));
        self::assertTrue($this->normalizer->supportsDenormalization($user, \stdClass::class));
    }

    public function testNormalizeWithNoReturnType(): void
    {
        $object = new Fixtures\UserWithYearOfBirth(1, 'Foo', 37);
        $expected = ['id' => 1, 'name' => 'Foo', 'age' => 37, 'yearOfBirth' => (((int) date('Y')) - 37)];

        $normalized = $this->normalizer->normalize($object, null, ['groups' => ['read']]);
        self::assertIsArray($normalized);
        self::assertEquals($expected['id'], $normalized['id']);
        self::assertEquals($expected['name'], $normalized['name']);
        self::assertEquals($expected['age'], $normalized['age']);
        self::assertEquals($expected['yearOfBirth'], $normalized['yearOfBirth']);
    }

    public function testItUsesSerializerContext(): void
    {
        $normalizer = new AutoMapperNormalizer(
            new class() implements AutoMapperInterface, AutoMapperRegistryInterface {
                public function map(array|object|null $source, string|array|object $target, array $context = []): array|object|null
                {
                    return $context;
                }

                public function getMapper(string $source, string $target): MapperInterface
                {
                    return new class() implements MapperInterface {
                        public function &map($value, array $context = []): mixed
                        {
                            return $value;
                        }
                    };
                }

                public function hasMapper(string $source, string $target): bool
                {
                    return true;
                }
            }
        );

        $context = $normalizer->normalize(new Fixtures\User(1, 'Jack', 37), 'json', [
            AbstractNormalizer::GROUPS => ['foo'],
            AbstractNormalizer::ATTRIBUTES => ['foo'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['foo'],
            AbstractNormalizer::OBJECT_TO_POPULATE => [],
            AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1,
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => 'circular-reference-handler',
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
            'custom-context' => 'some custom context',
            MapperContext::ALLOWED_ATTRIBUTES => 'some ignored context',
        ]);

        self::assertSame(
            [
                MapperContext::GROUPS => ['foo'],
                MapperContext::ALLOWED_ATTRIBUTES => ['foo'],
                MapperContext::IGNORED_ATTRIBUTES => ['foo'],
                MapperContext::TARGET_TO_POPULATE => [],
                MapperContext::CIRCULAR_REFERENCE_LIMIT => 1,
                MapperContext::CIRCULAR_REFERENCE_HANDLER => 'circular-reference-handler',
                MapperContext::DATETIME_FORMAT => 'Y-m-d',
                MapperContext::NORMALIZER_FORMAT => 'json',
                'custom-context' => 'some custom context',
            ],
            $context
        );

        $context = $normalizer->normalize(new Fixtures\User(1, 'Jack', 37), 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => 'bad-object',
        ]);

        self::assertSame([
            MapperContext::NORMALIZER_FORMAT => 'json',
        ], $context);
    }

    public function testItUsesSerializerDateFormatBasedOnSerializerContext(): void
    {
        self::assertSame(
            ['dateTime' => '2021-01-01'],
            $this->normalizer->normalize(
                new ObjectWithDateTime(new \DateTimeImmutable('2021-01-01 12:00:00')),
                'json',
                [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d']
            )
        );

        self::assertEquals(
            new ObjectWithDateTime(new \DateTimeImmutable('2023-01-24 00:00:00')),
            $this->normalizer->denormalize(
                ['dateTime' => '24-01-2023'],
                ObjectWithDateTime::class,
                null,
                [MapperContext::DATETIME_FORMAT => '!d-m-Y']
            )
        );
    }
}
