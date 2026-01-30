<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AutoMapper\Tests\ObjectMapper;

use AutoMapper\ObjectMapper\ObjectMapper;
use AutoMapper\Tests\AutoMapperBuilder;
use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\Tests\ObjectMapper\Fixtures\A;
use AutoMapper\Tests\ObjectMapper\Fixtures\B;
use AutoMapper\Tests\ObjectMapper\Fixtures\C;
use AutoMapper\Tests\ObjectMapper\Fixtures\ClassWithoutTarget;
use AutoMapper\Tests\ObjectMapper\Fixtures\D;
use AutoMapper\Tests\ObjectMapper\Fixtures\DeeperRecursion\Recursive;
use AutoMapper\Tests\ObjectMapper\Fixtures\DeeperRecursion\RecursiveDto;
use AutoMapper\Tests\ObjectMapper\Fixtures\DeeperRecursion\Relation;
use AutoMapper\Tests\ObjectMapper\Fixtures\DeeperRecursion\RelationDto;
use AutoMapper\Tests\ObjectMapper\Fixtures\DefaultLazy\OrderSource;
use AutoMapper\Tests\ObjectMapper\Fixtures\DefaultLazy\OrderTarget;
use AutoMapper\Tests\ObjectMapper\Fixtures\DefaultLazy\UserSource;
use AutoMapper\Tests\ObjectMapper\Fixtures\DefaultLazy\UserTarget;
use AutoMapper\Tests\ObjectMapper\Fixtures\DefaultValueStdClass\TargetDto;
use AutoMapper\Tests\ObjectMapper\Fixtures\EmbeddedMapping\Address;
use AutoMapper\Tests\ObjectMapper\Fixtures\EmbeddedMapping\User as UserEmbeddedMapping;
use AutoMapper\Tests\ObjectMapper\Fixtures\EmbeddedMapping\UserDto;
use AutoMapper\Tests\ObjectMapper\Fixtures\Flatten\TargetUser;
use AutoMapper\Tests\ObjectMapper\Fixtures\Flatten\User;
use AutoMapper\Tests\ObjectMapper\Fixtures\Flatten\UserProfile;
use AutoMapper\Tests\ObjectMapper\Fixtures\HydrateObject\SourceOnly;
use AutoMapper\Tests\ObjectMapper\Fixtures\InitializedConstructor\A as InitializedConstructorA;
use AutoMapper\Tests\ObjectMapper\Fixtures\InitializedConstructor\B as InitializedConstructorB;
use AutoMapper\Tests\ObjectMapper\Fixtures\InitializedConstructor\C as InitializedConstructorC;
use AutoMapper\Tests\ObjectMapper\Fixtures\InitializedConstructor\D as InitializedConstructorD;
use AutoMapper\Tests\ObjectMapper\Fixtures\InstanceCallback\A as InstanceCallbackA;
use AutoMapper\Tests\ObjectMapper\Fixtures\InstanceCallback\B as InstanceCallbackB;
use AutoMapper\Tests\ObjectMapper\Fixtures\InstanceCallbackWithArguments\A as InstanceCallbackWithArgumentsA;
use AutoMapper\Tests\ObjectMapper\Fixtures\InstanceCallbackWithArguments\B as InstanceCallbackWithArgumentsB;
use AutoMapper\Tests\ObjectMapper\Fixtures\LazyFoo;
use AutoMapper\Tests\ObjectMapper\Fixtures\MapTargetToSource\A as MapTargetToSourceA;
use AutoMapper\Tests\ObjectMapper\Fixtures\MapTargetToSource\B as MapTargetToSourceB;
use AutoMapper\Tests\ObjectMapper\Fixtures\MultipleTargetProperty\A as MultipleTargetPropertyA;
use AutoMapper\Tests\ObjectMapper\Fixtures\MultipleTargetProperty\B as MultipleTargetPropertyB;
use AutoMapper\Tests\ObjectMapper\Fixtures\MultipleTargetProperty\C as MultipleTargetPropertyC;
use AutoMapper\Tests\ObjectMapper\Fixtures\MultipleTargets\A as MultipleTargetsA;
use AutoMapper\Tests\ObjectMapper\Fixtures\MultipleTargets\C as MultipleTargetsC;
use AutoMapper\Tests\ObjectMapper\Fixtures\MyProxy;
use AutoMapper\Tests\ObjectMapper\Fixtures\PartialInput\FinalInput;
use AutoMapper\Tests\ObjectMapper\Fixtures\PartialInput\PartialInput;
use AutoMapper\Tests\ObjectMapper\Fixtures\PromotedConstructor\Source as PromotedConstructorSource;
use AutoMapper\Tests\ObjectMapper\Fixtures\PromotedConstructor\Target as PromotedConstructorTarget;
use AutoMapper\Tests\ObjectMapper\Fixtures\PromotedConstructorWithMetadata\Source as PromotedConstructorWithMetadataSource;
use AutoMapper\Tests\ObjectMapper\Fixtures\PromotedConstructorWithMetadata\Target as PromotedConstructorWithMetadataTarget;
use AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty\ReadOnlyPromotedPropertyA;
use AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty\ReadOnlyPromotedPropertyAMapped;
use AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty\ReadOnlyPromotedPropertyB;
use AutoMapper\Tests\ObjectMapper\Fixtures\ReadOnlyPromotedProperty\ReadOnlyPromotedPropertyBMapped;
use AutoMapper\Tests\ObjectMapper\Fixtures\Recursion\AB;
use AutoMapper\Tests\ObjectMapper\Fixtures\Recursion\Dto;
use AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLoadedValue\LoadedValueService;
use AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLoadedValue\ServiceLoadedValueTransformer;
use AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLoadedValue\ValueToMap;
use AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLoadedValue\ValueToMapRelation;
use AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLocator\A as ServiceLocatorA;
use AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLocator\B as ServiceLocatorB;
use AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLocator\ConditionCallable;
use AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLocator\TransformCallable;
use AutoMapper\Tests\ObjectMapper\Fixtures\TargetTransform\SourceEntity;
use AutoMapper\Tests\ObjectMapper\Fixtures\TargetTransform\TargetDto as TargetTransformTargetDto;
use AutoMapper\Tests\ObjectMapper\Fixtures\TransformCollection\TransformCollectionA;
use AutoMapper\Tests\ObjectMapper\Fixtures\TransformCollection\TransformCollectionB;
use AutoMapper\Tests\ObjectMapper\Fixtures\TransformCollection\TransformCollectionC;
use AutoMapper\Tests\ObjectMapper\Fixtures\TransformCollection\TransformCollectionD;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use Symfony\Component\ObjectMapper\Exception\MappingException;
use Symfony\Component\ObjectMapper\Exception\MappingTransformException;
use Symfony\Component\ObjectMapper\Exception\NoSuchPropertyException;
use Symfony\Component\ObjectMapper\Metadata\Mapping;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ObjectMapperTest extends AutoMapperTestCase
{
    protected LoadedValueService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LoadedValueService();
    }

    #[DataProvider('mapProvider')]
    public function testMap($expect, $args, array $deps = [])
    {
        $mapper = $this->createObjectMapper();
        $mapped = $mapper->map(...$args);

        $this->assertEquals($expect, $mapped);
    }

    /**
     * @return iterable<array{0: object, 1: array, 2: array}>
     */
    public static function mapProvider(): iterable
    {
        $d = new D(baz: 'foo', bat: 'bar');
        $c = new C(foo: 'foo', bar: 'bar');
        $a = new A();
        $a->foo = 'test';
        $a->transform = 'test';
        $a->baz = 'me';
        $a->notinb = 'test';
        $a->relation = $c;
        $a->relationNotMapped = $d;

        $b = new B('test');
        $b->transform = 'TEST';
        $b->baz = 'me';
        $b->nomap = false;
        $b->concat = 'shouldtestme';
        $b->relation = $d;
        $b->relationNotMapped = $d;
        yield [$b, [$a]];

        $c = clone $b;
        $c->id = 1;
        yield [$c, [$a, $c]];

        $d = clone $b;
        // with propertyAccessor we call the getter
        $d->concat = 'shouldtestme';

        yield [$d, [$a], [new ReflectionObjectMapperMetadataFactory(), PropertyAccess::createPropertyAccessor()]];

        yield [new MultipleTargetsC(foo: 'bar'), [new MultipleTargetsA()]];
    }

    public function testHasNothingToMapTo()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Mapping target not found for source "class@anonymous".');
        $this->createObjectMapper()->map(new class {});
    }

    public function testHasNothingToMapToWithNamedClass()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(\sprintf('Mapping target not found for source "%s".', ClassWithoutTarget::class));
        $this->createObjectMapper()->map(new ClassWithoutTarget());
    }

    public function testTargetNotFound()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(\sprintf('Mapping target class "InexistantClass" does not exist for source "%s".', ClassWithoutTarget::class));
        $this->createObjectMapper()->map(new ClassWithoutTarget(), 'InexistantClass');
    }

    public function testRecursion()
    {
        $ab = new AB();
        $ab->ab = $ab;
        $mapper = $this->createObjectMapper();
        $mapped = $mapper->map($ab);
        $this->assertInstanceOf(Dto::class, $mapped);
        $this->assertSame($mapped, $mapped->dto);
    }

    public function testDeeperRecursion()
    {
        $recursive = new Recursive();
        $recursive->name = 'hi';
        $recursive->relation = new Relation();
        $recursive->relation->recursion = $recursive;
        $mapper = $this->createObjectMapper();
        $mapped = $mapper->map($recursive);
        $this->assertSame($mapped->relation->recursion, $mapped);
        $this->assertInstanceOf(RecursiveDto::class, $mapped);
        $this->assertInstanceOf(RelationDto::class, $mapped->relation);
    }

    public function testMapWithInitializedConstructor()
    {
        $a = new InitializedConstructorA();
        $mapper = $this->createObjectMapper();
        $b = $mapper->map($a, InitializedConstructorB::class);
        $this->assertInstanceOf(InitializedConstructorB::class, $b);
        $this->assertEquals($b->tags, ['foo', 'bar']);
    }

    public function testMapReliesOnConstructorsOwnInitialization()
    {
        $expected = 'bar';

        $mapper = $this->createObjectMapper();

        $source = new \stdClass();
        $source->bar = $expected;

        $c = $mapper->map($source, InitializedConstructorC::class);

        $this->assertInstanceOf(InitializedConstructorC::class, $c);
        $this->assertEquals($expected, $c->bar);
    }

    public function testMapConstructorArgumentsDifferFromClassFields()
    {
        $expected = 'bar';

        $mapper = $this->createObjectMapper();

        $source = new \stdClass();
        $source->bar = $expected;

        $actual = $mapper->map($source, InitializedConstructorD::class);

        $this->assertInstanceOf(InitializedConstructorD::class, $actual);
        $this->assertStringContainsStringIgnoringCase($expected, $actual->barUpperCase);
    }

    public function testMapToWithInstanceHook()
    {
        $a = new InstanceCallbackA();
        $mapper = $this->createObjectMapper();
        $b = $mapper->map($a, InstanceCallbackB::class);
        $this->assertInstanceOf(InstanceCallbackB::class, $b);
        $this->assertSame($b->getId(), 1);
        $this->assertSame($b->name, 'test');
    }

    public function testMapToWithInstanceHookWithArguments()
    {
        $a = new InstanceCallbackWithArgumentsA();
        $mapper = $this->createObjectMapper();
        $b = $mapper->map($a);
        $this->assertInstanceOf(InstanceCallbackWithArgumentsB::class, $b);
        $this->assertSame($a, $b->transformSource);
    }

    public function testMultipleMapProperty()
    {
        $u = new User(email: 'hello@example.com', profile: new UserProfile(firstName: 'soyuka', lastName: 'arakusa'));
        $mapper = $this->createObjectMapper();
        $b = $mapper->map($u);
        $this->assertInstanceOf(TargetUser::class, $b);
        $this->assertSame($b->firstName, 'soyuka');
        $this->assertSame($b->lastName, 'arakusa');
    }

    public function testServiceLocator()
    {
        $a = new ServiceLocatorA();
        $a->foo = 'nok';

        $mapper = $this->createObjectMapper();

        $b = $mapper->map($a);
        $this->assertSame($b->bar, 'notmapped');
        $this->assertInstanceOf(ServiceLocatorB::class, $b);

        $a->foo = 'ok';
        $b = $mapper->map($a);
        $this->assertInstanceOf(ServiceLocatorB::class, $b);
        $this->assertSame($b->bar, 'transformedok');
    }

    public function testSourceOnly()
    {
        $a = new \stdClass();
        $a->name = 'test';
        $mapper = $this->createObjectMapper();
        $mapped = $mapper->map($a, SourceOnly::class);
        $this->assertInstanceOf(SourceOnly::class, $mapped);
        $this->assertSame('test', $mapped->mappedName);
    }

    public function testSourceOnlyWithMagicMethods()
    {
        $mapper = $this->createObjectMapper();
        $a = new class {
            public function __isset($key): bool
            {
                return 'name' === $key;
            }

            public function __get(string $key): string
            {
                return match ($key) {
                    'name' => 'test',
                    default => throw new \LogicException($key),
                };
            }
        };

        $mapped = $mapper->map($a, SourceOnly::class);
        $this->assertInstanceOf(SourceOnly::class, $mapped);
        $this->assertSame('test', $mapped->mappedName);
    }

    public function testTransformToWrongValueType()
    {
        $this->expectException(MappingTransformException::class);
        $this->expectExceptionMessage('Cannot map "stdClass" to a non-object target of type "string".');

        $u = new \stdClass();
        $u->foo = 'bar';

        $metadata = $this->createStub(ObjectMapperMetadataFactoryInterface::class);
        $metadata->method('create')->with($u)->willReturn([new Mapping(target: \stdClass::class, transform: fn () => 'str')]);
        $mapper = new ObjectMapper(metadataFactory: $metadata);
        $mapper->map($u);
    }

    public function testTransformToWrongObject()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(\sprintf('Expected the mapped object to be an instance of "%s" but got "stdClass".', ClassWithoutTarget::class));

        $u = new \stdClass();
        $u->foo = 'bar';

        $metadata = $this->createStub(ObjectMapperMetadataFactoryInterface::class);
        $metadata->method('create')->with($u)->willReturn([new Mapping(target: ClassWithoutTarget::class, transform: fn () => new \stdClass())]);
        $mapper = new ObjectMapper(metadataFactory: $metadata);
        $mapper->map($u);
    }

    public function testMapTargetToSource()
    {
        $a = new MapTargetToSourceA('str');
        $mapper = $this->createObjectMapper();
        $b = $mapper->map($a, MapTargetToSourceB::class);
        $this->assertInstanceOf(MapTargetToSourceB::class, $b);
        $this->assertSame('str', $b->target);
    }

    public function testMultipleTargetMapProperty()
    {
        $u = new MultipleTargetPropertyA();

        $mapper = $this->createObjectMapper();
        $b = $mapper->map($u, MultipleTargetPropertyB::class);
        $this->assertInstanceOf(MultipleTargetPropertyB::class, $b);
        $this->assertEquals('TEST', $b->foo);
        $c = $mapper->map($u, MultipleTargetPropertyC::class);
        $this->assertInstanceOf(MultipleTargetPropertyC::class, $c);
        $this->assertEquals('test', $c->bar);
        $this->assertEquals('donotmap', $c->foo);
        $this->assertEquals('foo', $c->doesNotExistInTargetB);
    }

    public function testDefaultValueStdClass()
    {
        $this->markTestSkipped('This use case is supported by AutoMapper, as we skip non existing properties by default.');

        $this->expectException(NoSuchPropertyException::class);
        $u = new \stdClass();
        $u->id = 'abc';
        $mapper = $this->createObjectMapper();
        $b = $mapper->map($u, TargetDto::class);
    }

    public function testDefaultValueStdClassWithPropertyInfo()
    {
        $u = new \stdClass();
        $u->id = 'abc';
        $mapper = $this->createObjectMapper();
        $b = $mapper->map($u, TargetDto::class);
        $this->assertInstanceOf(TargetDto::class, $b);
        $this->assertSame('abc', $b->id);
        $this->assertNull($b->optional);
    }

    #[DataProvider('objectMapperProvider')]
    public function testUpdateObjectWithConstructorPromotedProperties(ObjectMapperInterface $mapper)
    {
        $a = new PromotedConstructorSource(1, 'foo');
        $b = new PromotedConstructorTarget(1, 'bar');
        $v = $mapper->map($a, $b);
        $this->assertSame($v->name, 'foo');
    }

    #[DataProvider('objectMapperProvider')]
    public function testUpdateMappedObjectWithAdditionalConstructorPromotedProperties(ObjectMapperInterface $mapper)
    {
        $a = new PromotedConstructorWithMetadataSource(3, 'foo-will-get-updated');
        $b = new PromotedConstructorWithMetadataTarget('notOnSourceButRequired', 1, 'bar');

        $v = $mapper->map($a, $b);

        $this->assertSame($v->name, $a->name);
        $this->assertSame($v->number, $a->number);
    }

    /**
     * @return iterable<array{0: ObjectMapperInterface}>
     */
    public static function objectMapperProvider(): iterable
    {
        yield [new ObjectMapper()];
    }

    public function testMapInitializesLazyObject()
    {
        $lazy = new LazyFoo();
        $mapper = $this->createObjectMapper();
        $mapper->map($lazy, \stdClass::class);
        $this->assertTrue($lazy->isLazyObjectInitialized());
    }

    #[RequiresPhp('>=8.4')]
    public function testMapInitializesNativePhp84LazyObject()
    {
        $initialized = false;
        $initializer = function () use (&$initialized) {
            $initialized = true;

            $p = new MyProxy();
            $p->name = 'test';

            return $p;
        };

        $r = new \ReflectionClass(MyProxy::class);
        $lazyObj = $r->newLazyProxy($initializer);
        $this->assertFalse($initialized);
        $mapper = $this->createObjectMapper();
        $d = $mapper->map($lazyObj, MyProxy::class);
        $this->assertSame('test', $d->name);
        $this->assertTrue($initialized);
    }

    public function testDecorateObjectMapper()
    {
        $this->markTestSkipped('This use case is not supported by AutoMapper.');

        $mapper = $this->createObjectMapper();
        $myMapper = new class($mapper) implements ObjectMapperInterface {
            public function __construct(
                private ObjectMapperInterface $mapper,
            ) {
                $this->mapper = $mapper->withObjectMapper($this);
            }

            public function map(object $source, object|string|null $target = null): object
            {
                $mapped = $this->mapper->map($source, $target);

                if ($source instanceof C) {
                    $mapped->baz = 'got decorated';
                }

                return $mapped;
            }
        };

        $d = new D(baz: 'foo', bat: 'bar');
        $c = new C(foo: 'foo', bar: 'bar');
        $myNewD = $myMapper->map($c);
        $this->assertSame('got decorated', $myNewD->baz);

        $a = new A();
        $a->foo = 'test';
        $a->transform = 'test';
        $a->baz = 'me';
        $a->notinb = 'test';
        $a->relation = $c;
        $a->relationNotMapped = $d;

        $b = $myMapper->map($a);
        $this->assertSame('got decorated', $b->relation->baz);
    }

    #[DataProvider('validPartialInputProvider')]
    public function testMapPartially(PartialInput $actual, FinalInput $expected)
    {
        $mapper = $this->createObjectMapper();
        $this->assertEquals($expected, $mapper->map($actual));
    }

    public static function validPartialInputProvider(): iterable
    {
        $p = new PartialInput();
        $p->uuid = '6a9eb6dd-c4dc-4746-bb99-f6bad716acb2';
        $p->website = 'https://updated.website.com';

        $f = new FinalInput();
        $f->uuid = $p->uuid;
        $f->website = $p->website;

        yield [$p, $f];

        $p = new PartialInput();
        $p->uuid = '6a9eb6dd-c4dc-4746-bb99-f6bad716acb2';
        $p->website = null;

        $f = new FinalInput();
        $f->uuid = $p->uuid;

        yield [$p, $f];

        $p = new PartialInput();
        $p->uuid = '6a9eb6dd-c4dc-4746-bb99-f6bad716acb2';
        $p->website = 'https://updated.website.com';
        $p->email = 'updated@email.com';

        $f = new FinalInput();
        $f->uuid = $p->uuid;
        $f->website = $p->website;
        $f->email = $p->email;

        yield [$p, $f];
    }

    public function testMapWithSourceTransform()
    {
        $source = new SourceEntity();
        $source->name = 'test';

        $mapper = $this->createObjectMapper();
        $target = $mapper->map($source, TargetTransformTargetDto::class);

        $this->assertInstanceOf(TargetTransformTargetDto::class, $target);
        $this->assertTrue($target->transformed);
        $this->assertSame('test', $target->name);
    }

    public function testTransformCollection()
    {
        $u = new TransformCollectionA();
        $u->foo = [new TransformCollectionC('a'), new TransformCollectionC('b')];
        $mapper = $this->createObjectMapper();

        $transformed = $mapper->map($u, TransformCollectionB::class);

        $this->assertEquals([new TransformCollectionD('a'), new TransformCollectionD('b')], $transformed->foo);
    }

    #[RequiresPhp('>=8.4')]
    public function testEmbedsAreLazyLoadedByDefault()
    {
        $this->markTestSkipped('Lazy Loading is not enable by default and works differently.');

        $mapper = $this->createObjectMapper();
        $source = new OrderSource();
        $source->id = 123;
        $source->user = new UserSource();
        $source->user->name = 'Test User';
        $target = $mapper->map($source, OrderTarget::class);
        $this->assertInstanceOf(OrderTarget::class, $target);
        $this->assertSame(123, $target->id);
        $this->assertInstanceOf(UserTarget::class, $target->user);
        $refl = new \ReflectionClass(UserTarget::class);
        $this->assertTrue($refl->isUninitializedLazyObject($target->user));
        $this->assertSame('Test User', $target->user->name);
        $this->assertFalse($refl->isUninitializedLazyObject($target->user));
    }

    public function testSkipLazyGhostWithClassTransform()
    {
        $mapper = $this->createObjectMapper();

        $value = new ValueToMap();
        $value->relation = new ValueToMapRelation('test');

        $result = $mapper->map($value);
        $refl = new \ReflectionClass($result->relation);
        $this->assertFalse($refl->isUninitializedLazyObject($result->relation));

        $this->assertSame($result->relation, $this->service->get());
        $this->assertSame('test', $result->relation->name);
    }

    public function testMapEmbeddedProperties()
    {
        $dto = new UserDto(
            userAddressZipcode: '12345',
            userAddressCity: 'Test City',
            name: 'John Doe'
        );

        $mapper = $this->createObjectMapper();
        $user = $mapper->map($dto, UserEmbeddedMapping::class);

        $this->assertInstanceOf(UserEmbeddedMapping::class, $user);
        $this->assertSame('John Doe', $user->name);
        $this->assertInstanceOf(Address::class, $user->address);
        $this->assertSame('12345', $user->address->zipcode);
        $this->assertSame('Test City', $user->address->city);
    }

    public function testBugReportLazyLoadingPromotedReadonlyProperty()
    {
        $source = new ReadOnlyPromotedPropertyA(
            b: new ReadOnlyPromotedPropertyB(
                var2: 'bar',
            ),
            var1: 'foo',
        );

        $mapper = $this->createObjectMapper();
        $out = $mapper->map($source);

        $this->assertInstanceOf(ReadOnlyPromotedPropertyAMapped::class, $out);
        $this->assertInstanceOf(ReadOnlyPromotedPropertyBMapped::class, $out->b);
        $this->assertSame('foo', $out->var1);
        $this->assertSame('bar', $out->b->var2);
    }

    public function createObjectMapper(): ObjectMapperInterface
    {
        $metadataFactory = new ReflectionObjectMapperMetadataFactory();
        $this->service->load();

        return new ObjectMapper(autoMapper: AutoMapperBuilder::buildAutoMapper(extraServices: [
            new TransformCallable(),
            new ConditionCallable(),
            new ServiceLoadedValueTransformer($this->service, $metadataFactory),
        ]));
    }
}
