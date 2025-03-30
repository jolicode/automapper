<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\AutoMapper;
use AutoMapper\Configuration;
use AutoMapper\ConstructorStrategy;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Exception\CircularReferenceException;
use AutoMapper\Exception\InvalidMappingException;
use AutoMapper\Exception\MissingConstructorArgumentsException;
use AutoMapper\Exception\ReadOnlyTargetException;
use AutoMapper\MapperContext;
use AutoMapper\Tests\Fixtures\Address;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Tests\Fixtures\AddressDTOReadonlyClass;
use AutoMapper\Tests\Fixtures\AddressDTOWithReadonly;
use AutoMapper\Tests\Fixtures\AddressDTOWithReadonlyPromotedProperty;
use AutoMapper\Tests\Fixtures\AddressType;
use AutoMapper\Tests\Fixtures\AddressWithEnum;
use AutoMapper\Tests\Fixtures\ClassWithMapToContextAttribute;
use AutoMapper\Tests\Fixtures\ClassWithNullablePropertyInConstructor;
use AutoMapper\Tests\Fixtures\ClassWithPrivateProperty;
use AutoMapper\Tests\Fixtures\ConstructorWithDefaultValues;
use AutoMapper\Tests\Fixtures\ConstructorWithDefaultValuesAsObjects;
use AutoMapper\Tests\Fixtures\Dog;
use AutoMapper\Tests\Fixtures\Fish;
use AutoMapper\Tests\Fixtures\FooGenerator;
use AutoMapper\Tests\Fixtures\HasDateTime;
use AutoMapper\Tests\Fixtures\HasDateTimeImmutable;
use AutoMapper\Tests\Fixtures\HasDateTimeImmutableWithNullValue;
use AutoMapper\Tests\Fixtures\HasDateTimeInterfaceWithImmutableInstance;
use AutoMapper\Tests\Fixtures\HasDateTimeInterfaceWithMutableInstance;
use AutoMapper\Tests\Fixtures\HasDateTimeInterfaceWithNullValue;
use AutoMapper\Tests\Fixtures\HasDateTimeWithNullValue;
use AutoMapper\Tests\Fixtures\IntDTO;
use AutoMapper\Tests\Fixtures\ObjectWithDateTime;
use AutoMapper\Tests\Fixtures\Order;
use AutoMapper\Tests\Fixtures\PetOwner;
use AutoMapper\Tests\Fixtures\PetOwnerWithConstructorArguments;
use AutoMapper\Tests\Fixtures\SourceForConstructorWithDefaultValues;
use AutoMapper\Tests\Fixtures\Transformer\MoneyTransformerFactory;
use AutoMapper\Tests\Fixtures\Uninitialized;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class AutoMapperTest extends AutoMapperTestCase
{
    use VarDumperTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpVarDumper([
            \Throwable::class => function (\Throwable $e) {
                return [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                ];
            },
        ], CliDumper::DUMP_LIGHT_ARRAY);
    }

    public function testAutoMapping(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $address = new Address();
        $address->setCity('Toulon');
        $user = new Fixtures\User(1, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;
        $user->money = 20.10;

        /** @var Fixtures\UserDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserDTO::class);

        self::assertInstanceOf(Fixtures\UserDTO::class, $userDto);
        self::assertSame(1, $userDto->id);
        self::assertSame('yolo', $userDto->getName());
        self::assertSame(13, $userDto->age);
        self::assertCount(1, $userDto->addresses);
        self::assertInstanceOf(AddressDTO::class, $userDto->address);
        self::assertInstanceOf(AddressDTO::class, $userDto->addresses[0]);
        self::assertSame('Toulon', $userDto->address->city);
        self::assertSame('Toulon', $userDto->addresses[0]->city);
        self::assertIsArray($userDto->money);
        self::assertCount(1, $userDto->money);
        self::assertSame(20.10, $userDto->money[0]);
    }

    public function testAutoMapperFromArray(): void
    {
        AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $user = [
            'id' => 1,
            'address' => [
                'city' => 'Toulon',
            ],
            'createdAt' => '1987-04-30T06:00:00Z',
        ];

        /** @var Fixtures\UserDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserDTO::class);

        self::assertInstanceOf(Fixtures\UserDTO::class, $userDto);
        self::assertEquals(1, $userDto->id);
        self::assertInstanceOf(AddressDTO::class, $userDto->address);
        self::assertSame('Toulon', $userDto->address->city);
        self::assertInstanceOf(\DateTimeInterface::class, $userDto->createdAt);
        self::assertEquals(1987, $userDto->createdAt->format('Y'));
    }

    public function testAutoMapperFromArrayCustomDateTime(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'CustomDateTime_', dateTimeFormat: 'U');

        $customFormat = 'U';
        $dateTime = \DateTime::createFromFormat(\DateTime::RFC3339, '1987-04-30T06:00:00Z');
        $user = [
            'id' => 1,
            'address' => [
                'city' => 'Toulon',
            ],
            'createdAt' => $dateTime->format($customFormat),
        ];

        /** @var Fixtures\UserDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserDTO::class);

        self::assertInstanceOf(Fixtures\UserDTO::class, $userDto);
        self::assertEquals($dateTime->format($customFormat), $userDto->createdAt->format($customFormat));
    }

    public function testAutoMapperToArray(): void
    {
        $address = new Address();
        $address->setCity('Toulon');
        $user = new Fixtures\User(1, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;

        $userData = $this->autoMapper->map($user, 'array');

        self::assertIsArray($userData);
        self::assertEquals(1, $userData['id']);
        self::assertIsArray($userData['address']);
        self::assertIsString($userData['createdAt']);
    }

    public function testAutoMapperToArrayGroups(): void
    {
        $address = new Address();
        $address->setCity('Toulon');
        $user = new Fixtures\User(1, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;

        $userData = $this->autoMapper->map($user, 'array', [MapperContext::GROUPS => ['dummy']]);

        self::assertIsArray($userData);
        self::assertEmpty($userData);
    }

    public function testAutoMapperFromStdObject(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $user = new \stdClass();
        $user->id = 1;

        /** @var Fixtures\UserDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserDTO::class);

        self::assertInstanceOf(Fixtures\UserDTO::class, $userDto);
        self::assertEquals(1, $userDto->id);
    }

    public function testAutoMapperToStdObject(): void
    {
        $userDto = new Fixtures\UserDTO();
        $userDto->id = 1;

        $user = $this->autoMapper->map($userDto, \stdClass::class);

        self::assertInstanceOf(\stdClass::class, $user);
        self::assertEquals(1, $user->id);
    }

    public function testNotReadable(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'CustomDateTime_');

        $address = new Address();
        $address->setCity('test');

        $addressArray = $this->autoMapper->map($address, 'array');

        self::assertIsArray($addressArray);
        self::assertArrayNotHasKey('city', $addressArray);

        $addressMapped = $this->autoMapper->map($address, Address::class);

        self::assertInstanceOf(Address::class, $addressMapped);

        $property = (new \ReflectionClass($addressMapped))->getProperty('city');
        $property->setAccessible(true);

        $city = $property->getValue($addressMapped);

        self::assertNull($city);
    }

    public function testNoTransformer(): void
    {
        $addressFoo = new Fixtures\AddressFoo();
        $addressFoo->city = new Fixtures\CityFoo();
        $addressFoo->city->name = 'test';

        $addressBar = $this->autoMapper->map($addressFoo, Fixtures\AddressBar::class);

        self::assertInstanceOf(Fixtures\AddressBar::class, $addressBar);
        self::assertNull($addressBar->city);
    }

    public function testGroupsSourceTarget(): void
    {
        $foo = new Fixtures\Foo();
        $foo->setId(10);

        $bar = $this->autoMapper->map($foo, Fixtures\Bar::class, [MapperContext::GROUPS => ['group2']]);

        self::assertInstanceOf(Fixtures\Bar::class, $bar);
        self::assertEquals(10, $bar->getId());

        $bar = $this->autoMapper->map($foo, Fixtures\Bar::class, [MapperContext::GROUPS => ['group1', 'group3']]);

        self::assertInstanceOf(Fixtures\Bar::class, $bar);
        self::assertEquals(10, $bar->getId());

        $bar = $this->autoMapper->map($foo, Fixtures\Bar::class, [MapperContext::GROUPS => ['group1']]);

        self::assertInstanceOf(Fixtures\Bar::class, $bar);
        self::assertNull($bar->getId());

        $bar = $this->autoMapper->map($foo, Fixtures\Bar::class, [MapperContext::GROUPS => []]);

        self::assertInstanceOf(Fixtures\Bar::class, $bar);
        self::assertNull($bar->getId());

        $bar = $this->autoMapper->map($foo, Fixtures\Bar::class);

        self::assertInstanceOf(Fixtures\Bar::class, $bar);
        self::assertNull($bar->getId());
    }

    public function testGroupsToArray(): void
    {
        $foo = new Fixtures\Foo();
        $foo->setId(10);

        $fooArray = $this->autoMapper->map($foo, 'array', [MapperContext::GROUPS => ['group1']]);

        self::assertIsArray($fooArray);
        self::assertEquals(10, $fooArray['id']);

        $fooArray = $this->autoMapper->map($foo, 'array', [MapperContext::GROUPS => []]);

        self::assertIsArray($fooArray);
        self::assertArrayNotHasKey('id', $fooArray);

        $fooArray = $this->autoMapper->map($foo, 'array');

        self::assertIsArray($fooArray);
        self::assertArrayNotHasKey('id', $fooArray);
    }

    public function testSkippedGroups(): void
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(PropertyMetadataEvent::class, function (PropertyMetadataEvent $event) {
            $event->disableGroupsCheck = true;
        });

        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(eventDispatcher: $eventDispatcher, classPrefix: 'SkippedGroups_');

        $foo = new Fixtures\Foo();
        $foo->setId(10);

        $fooArray = $this->autoMapper->map($foo, 'array', [MapperContext::GROUPS => ['group1']]);

        self::assertIsArray($fooArray);
        self::assertEquals(10, $fooArray['id']);

        $fooArray = $this->autoMapper->map($foo, 'array', [MapperContext::GROUPS => []]);

        self::assertIsArray($fooArray);
        self::assertEquals(10, $fooArray['id']);

        $fooArray = $this->autoMapper->map($foo, 'array');

        self::assertIsArray($fooArray);
        self::assertEquals(10, $fooArray['id']);
    }

    public function testDeepCloning(): void
    {
        $nodeA = new Fixtures\Node();
        $nodeB = new Fixtures\Node();
        $nodeB->parent = $nodeA;
        $nodeC = new Fixtures\Node();
        $nodeC->parent = $nodeB;
        $nodeA->parent = $nodeC;

        $newNode = $this->autoMapper->map($nodeA, Fixtures\Node::class);

        self::assertInstanceOf(Fixtures\Node::class, $newNode);
        self::assertNotSame($newNode, $nodeA);
        self::assertInstanceOf(Fixtures\Node::class, $newNode->parent);
        self::assertNotSame($newNode->parent, $nodeA->parent);
        self::assertInstanceOf(Fixtures\Node::class, $newNode->parent->parent);
        self::assertNotSame($newNode->parent->parent, $nodeA->parent->parent);
        self::assertInstanceOf(Fixtures\Node::class, $newNode->parent->parent->parent);
        self::assertSame($newNode, $newNode->parent->parent->parent);
    }

    public function testDeepCloningArray(): void
    {
        $nodeA = new Fixtures\Node();
        $nodeB = new Fixtures\Node();
        $nodeB->parent = $nodeA;
        $nodeC = new Fixtures\Node();
        $nodeC->parent = $nodeB;
        $nodeA->parent = $nodeC;

        $newNode = $this->autoMapper->map($nodeA, 'array');

        self::assertIsArray($newNode);
        self::assertIsArray($newNode['parent']);
        self::assertIsArray($newNode['parent']['parent']);
        self::assertIsArray($newNode['parent']['parent']['parent']);
        self::assertSame($newNode, $newNode['parent']['parent']['parent']);
    }

    public function testCircularReferenceArray(): void
    {
        $nodeA = new Fixtures\Node();
        $nodeB = new Fixtures\Node();

        $nodeA->childs[] = $nodeB;
        $nodeB->childs[] = $nodeA;

        $newNode = $this->autoMapper->map($nodeA, 'array');

        self::assertIsArray($newNode);
        self::assertIsArray($newNode['childs'][0]);
        self::assertIsArray($newNode['childs'][0]['childs'][0]);
        self::assertSame($newNode, $newNode['childs'][0]['childs'][0]);
    }

    public function testConstructor(): void
    {
        $user = new Fixtures\UserDTO();
        $user->id = 10;
        $user->setName('foo');
        $user->age = 3;
        /** @var Fixtures\UserConstructorDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserConstructorDTO::class);

        self::assertInstanceOf(Fixtures\UserConstructorDTO::class, $userDto);
        self::assertSame('10', $userDto->getId());
        self::assertSame('foo', $userDto->getName());
        self::assertSame(3, $userDto->getAge());
        self::assertTrue($userDto->getConstructor());
    }

    public function testConstructorWithNullSource(): void
    {
        $user = new Fixtures\UserDTO();
        $user->id = 10;
        $user->setName('foo');
        $user->age = null;
        /** @var Fixtures\UserConstructorDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserConstructorDTO::class);

        self::assertInstanceOf(Fixtures\UserConstructorDTO::class, $userDto);
        self::assertSame('10', $userDto->getId());
        self::assertSame('foo', $userDto->getName());
        // since age is null we take default value from constructor
        self::assertSame(30, $userDto->getAge());
        self::assertTrue($userDto->getConstructor());
    }

    public function testConstructorArrayArgumentFromContext(): void
    {
        $data = ['baz' => 'baz'];
        /** @var ConstructorWithDefaultValues $userDto */
        $object = $this->autoMapper->map($data, ConstructorWithDefaultValues::class, [MapperContext::CONSTRUCTOR_ARGUMENTS => [
            ConstructorWithDefaultValues::class => ['someOtters' => [1]],
        ]]);

        self::assertInstanceOf(ConstructorWithDefaultValues::class, $object);
        self::assertSame('baz', $object->baz);
        self::assertSame([1], $object->someOtters);
    }

    public function testConstructorNotAllowed(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true, constructorStrategy: ConstructorStrategy::NEVER, classPrefix: 'NotAllowedMapper_');

        $user = new Fixtures\UserDTO();
        $user->id = 10;
        $user->setName('foo');
        $user->age = 3;

        /** @var Fixtures\UserConstructorDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserConstructorDTO::class);

        self::assertInstanceOf(Fixtures\UserConstructorDTO::class, $userDto);
        self::assertSame('10', $userDto->getId());
        self::assertSame('foo', $userDto->getName());
        self::assertSame(3, $userDto->getAge());
        self::assertFalse($userDto->getConstructor());
    }

    public function testConstructorForced(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(constructorStrategy: ConstructorStrategy::ALWAYS, classPrefix: 'AlwaysConstructorMapper_');

        $data = ['baz' => 'baz'];
        /** @var ConstructorWithDefaultValues $object */
        $object = $this->autoMapper->map($data, ConstructorWithDefaultValues::class);

        self::assertInstanceOf(ConstructorWithDefaultValues::class, $object);
        self::assertSame(1, $object->foo);
        self::assertSame(0, $object->bar);
        self::assertSame('baz', $object->baz);

        $data = new SourceForConstructorWithDefaultValues();
        $data->foo = 10;
        /** @var ConstructorWithDefaultValues $object */
        $object = $this->autoMapper->map($data, ConstructorWithDefaultValues::class, [MapperContext::CONSTRUCTOR_ARGUMENTS => [
            ConstructorWithDefaultValues::class => ['baz' => 'test'],
        ]]);

        self::assertInstanceOf(ConstructorWithDefaultValues::class, $object);
        self::assertSame(10, $object->foo);
        self::assertSame(0, $object->bar);
        self::assertSame('test', $object->baz);
    }

    public function testConstructorForcedException(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(constructorStrategy: ConstructorStrategy::ALWAYS, classPrefix: 'AlwaysConstructorMapper_');
        $data = new SourceForConstructorWithDefaultValues();
        $data->foo = 10;

        $this->expectException(MissingConstructorArgumentsException::class);

        $this->autoMapper->map($data, ConstructorWithDefaultValues::class);
    }

    public function testConstructorWithDefaultFromStdClass(): void
    {
        $data = (object) ['baz' => 'baz'];
        /** @var ConstructorWithDefaultValues $object */
        $object = $this->autoMapper->map($data, ConstructorWithDefaultValues::class);

        self::assertInstanceOf(ConstructorWithDefaultValues::class, $object);
    }

    public function testConstructorWithDefault(): void
    {
        $user = new Fixtures\UserDTONoAge();
        $user->id = 10;
        $user->name = 'foo';
        /** @var Fixtures\UserConstructorDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserConstructorDTO::class);

        self::assertInstanceOf(Fixtures\UserConstructorDTO::class, $userDto);
        self::assertSame('10', $userDto->getId());
        self::assertSame('foo', $userDto->getName());
        self::assertSame(30, $userDto->getAge());
    }

    public function testConstructorWithDefaultsAsObjects(): void
    {
        $data = ['baz' => 'baz'];
        /** @var ConstructorWithDefaultValuesAsObjects $object */
        $object = $this->autoMapper->map($data, ConstructorWithDefaultValuesAsObjects::class);

        self::assertInstanceOf(ConstructorWithDefaultValuesAsObjects::class, $object);
        self::assertInstanceOf(\DateTimeImmutable::class, $object->date);
        self::assertInstanceOf(IntDTO::class, $object->IntDTO);
        self::assertSame('baz', $object->baz);

        $stdClassData = (object) $data;
        /** @var ConstructorWithDefaultValuesAsObjects $object */
        $object = $this->autoMapper->map($stdClassData, ConstructorWithDefaultValuesAsObjects::class);

        self::assertInstanceOf(ConstructorWithDefaultValuesAsObjects::class, $object);
        self::assertInstanceOf(\DateTimeImmutable::class, $object->date);
        self::assertInstanceOf(IntDTO::class, $object->IntDTO);
        self::assertSame('baz', $object->baz);
    }

    public function testConstructorDisable(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $user = new Fixtures\UserDTONoName();
        $user->id = 10;
        /** @var Fixtures\UserConstructorDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserConstructorDTO::class);

        self::assertInstanceOf(Fixtures\UserConstructorDTO::class, $userDto);
        self::assertSame('10', $userDto->getId());
        self::assertNull($userDto->getName());
        self::assertNull($userDto->getAge());
    }

    public function testObjectToPopulate(): void
    {
        $user = new Fixtures\User(1, 'yolo', '13');
        $userDtoToPopulate = new Fixtures\UserDTO();

        $userDto = $this->autoMapper->map($user, Fixtures\UserDTO::class, [MapperContext::TARGET_TO_POPULATE => $userDtoToPopulate]);

        self::assertSame($userDtoToPopulate, $userDto);
    }

    public function testObjectToPopulateWithoutContext(): void
    {
        $user = new Fixtures\User(1, 'yolo', '13');
        $userDtoToPopulate = new Fixtures\UserDTO();

        $userDto = $this->autoMapper->map($user, $userDtoToPopulate);

        self::assertSame($userDtoToPopulate, $userDto);
    }

    public function testArrayToPopulate(): void
    {
        $user = new Fixtures\User(1, 'yolo', '13');
        $array = [];
        $arrayMapped = $this->autoMapper->map($user, $array);

        self::assertIsArray($arrayMapped);
        self::assertSame(1, $arrayMapped['id']);
        self::assertSame('yolo', $arrayMapped['name']);
        self::assertSame('13', $arrayMapped['age']);
    }

    public function testCircularReferenceLimitOnContext(): void
    {
        $nodeA = new Fixtures\Node();
        $nodeA->parent = $nodeA;

        $context = new MapperContext();
        $context->setCircularReferenceLimit(1);

        $this->expectException(CircularReferenceException::class);

        $this->autoMapper->map($nodeA, 'array', $context->toArray());
    }

    public function testCircularReferenceHandlerOnContext(): void
    {
        $nodeA = new Fixtures\Node();
        $nodeA->parent = $nodeA;

        $context = new MapperContext();
        $context->setCircularReferenceHandler(function () {
            return 'foo';
        });

        $nodeArray = $this->autoMapper->map($nodeA, 'array', $context->toArray());

        self::assertSame('foo', $nodeArray['parent']);
    }

    public function testAllowedAttributes(): void
    {
        $user = new Fixtures\User(1, 'yolo', '13');
        $address = new Address();
        $address->setCity('some city');
        $user->setAddress($address);

        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        /** @var Fixtures\UserDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserDTO::class, [MapperContext::ALLOWED_ATTRIBUTES => ['id', 'age', 'address']]);

        self::assertNull($userDto->getName());
        self::assertInstanceOf(AddressDTO::class, $userDto->address);
        self::assertSame('some city', $userDto->address->city);
    }

    public function testIgnoredAttributes(): void
    {
        $user = new Fixtures\User(1, 'yolo', '13');
        $userDto = $this->autoMapper->map($user, Fixtures\UserDTO::class, [MapperContext::IGNORED_ATTRIBUTES => ['name']]);

        self::assertNull($userDto->getName());
    }

    public function testNameConverter(): void
    {
        if (Kernel::MAJOR_VERSION >= 7 && Kernel::MINOR_VERSION >= 2) {
            $nameConverter = new class() implements NameConverterInterface {
                public function normalize($propertyName, ?string $class = null, ?string $format = null, array $context = []): string
                {
                    if ('id' === $propertyName) {
                        return '@id';
                    }

                    return $propertyName;
                }

                public function denormalize($propertyName, ?string $class = null, ?string $format = null, array $context = []): string
                {
                    if ('@id' === $propertyName) {
                        return 'id';
                    }

                    return $propertyName;
                }
            };
        } else {
            $nameConverter = new class() implements AdvancedNameConverterInterface {
                public function normalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
                {
                    if ('id' === $propertyName) {
                        return '@id';
                    }

                    return $propertyName;
                }

                public function denormalize(string $propertyName, ?string $class = null, ?string $format = null, array $context = []): string
                {
                    if ('@id' === $propertyName) {
                        return 'id';
                    }

                    return $propertyName;
                }
            };
        }

        $autoMapper = AutoMapper::create(new Configuration(classPrefix: 'Mapper2_'), nameConverter: $nameConverter);
        $user = new Fixtures\User(1, 'yolo', '13');

        $userArray = $autoMapper->map($user, 'array');

        self::assertIsArray($userArray);
        self::assertArrayHasKey('@id', $userArray);
        self::assertSame(1, $userArray['@id']);
    }

    public function testDefaultArguments(): void
    {
        $user = new Fixtures\UserDTONoAge();
        $user->id = 10;
        $user->name = 'foo';

        $context = new MapperContext();
        $context->setConstructorArgument(Fixtures\UserConstructorDTO::class, 'age', 50);

        /** @var Fixtures\UserConstructorDTO $userDto */
        $userDto = $this->autoMapper->map($user, Fixtures\UserConstructorDTO::class, $context->toArray());

        self::assertInstanceOf(Fixtures\UserConstructorDTO::class, $userDto);
        self::assertSame(50, $userDto->getAge());
    }

    public function testDiscriminator(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'Discriminator');

        $data = [
            'type' => 'cat',
        ];

        $pet = $this->autoMapper->map($data, Fixtures\Pet::class);

        self::assertInstanceOf(Fixtures\Cat::class, $pet);
    }

    public function testInvalidMappingBothArray(): void
    {
        $this->expectException(InvalidMappingException::class);

        $data = ['test' => 'foo'];
        $array = $this->autoMapper->map($data, 'array');
    }

    public function testNoAutoRegister(): void
    {
        $this->expectException(InvalidMappingException::class);

        $automapper = AutoMapper::create(new Configuration(autoRegister: false, classPrefix: 'NoAutoRegister_'));
        $automapper->getMapper(Fixtures\User::class, Fixtures\UserDTO::class);
    }

    public function testStrictTypes(): void
    {
        $this->expectException(\TypeError::class);

        $automapper = AutoMapper::create(new Configuration(strictTypes: true, classPrefix: 'StrictTypes_'));
        $data = ['foo' => 1.1];
        $automapper->map($data, IntDTO::class);
    }

    public function testStrictTypesFromMapper(): void
    {
        $this->expectException(\TypeError::class);

        $automapper = AutoMapper::create(new Configuration(strictTypes: false, classPrefix: 'StrictTypesFromMapper_'));
        $data = ['foo' => 1.1];
        $automapper->map($data, Fixtures\IntDTOWithMapper::class);
    }

    public function testWithMixedArray(): void
    {
        $user = new Fixtures\User(1, 'yolo', '13');
        $user->setProperties(['foo' => 'bar']);

        /** @var Fixtures\UserDTOProperties $dto */
        $dto = $this->autoMapper->map($user, Fixtures\UserDTOProperties::class);

        self::assertInstanceOf(Fixtures\UserDTOProperties::class, $dto);
        self::assertSame(['foo' => 'bar'], $dto->getProperties());
    }

    public function testCustomTransformerFromArrayToObject(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true, transformerFactories: [new MoneyTransformerFactory()]);

        $data = [
            'id' => 4582,
            'price' => [
                'amount' => 1000,
                'currency' => 'EUR',
            ],
        ];
        $order = $this->autoMapper->map($data, Order::class);

        self::assertInstanceOf(Order::class, $order);
        self::assertInstanceOf(\Money\Money::class, $order->price);
        self::assertEquals(1000, $order->price->getAmount());
        self::assertEquals('EUR', $order->price->getCurrency()->getCode());
    }

    public function testCustomTransformerFromObjectToArray(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(transformerFactories: [new MoneyTransformerFactory()]);

        $order = new Order();
        $order->id = 4582;
        $order->price = new \Money\Money(1000, new \Money\Currency('EUR'));
        $data = $this->autoMapper->map($order, 'array');

        self::assertIsArray($data);
        self::assertEquals(4582, $data['id']);
        self::assertIsArray($data['price']);
        self::assertEquals(1000, $data['price']['amount']);
        self::assertEquals('EUR', $data['price']['currency']);
    }

    public function testCustomTransformerFromObjectToObject(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(transformerFactories: [new MoneyTransformerFactory()]);

        $order = new Order();
        $order->id = 4582;
        $order->price = new \Money\Money(1000, new \Money\Currency('EUR'));
        $newOrder = new Order();
        $newOrder = $this->autoMapper->map($order, $newOrder);

        self::assertInstanceOf(Order::class, $newOrder);
        self::assertInstanceOf(\Money\Money::class, $newOrder->price);
        self::assertEquals(1000, $newOrder->price->getAmount());
        self::assertEquals('EUR', $newOrder->price->getCurrency()->getCode());
    }

    public function testAdderAndRemoverWithClass(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $petOwner = [
            'pets' => [
                ['type' => 'cat', 'name' => 'Félix'],
                ['type' => 'dog', 'name' => 'Coco', 'bark' => 'Wouf'],
            ],
        ];

        $petOwnerData = $this->autoMapper->map($petOwner, PetOwner::class);

        self::assertIsArray($petOwnerData->getPets());
        self::assertCount(2, $petOwnerData->getPets());
        self::assertSame('Félix', $petOwnerData->getPets()[0]->name);
        self::assertSame('cat', $petOwnerData->getPets()[0]->type);
        self::assertSame('Coco', $petOwnerData->getPets()[1]->name);
        self::assertSame('dog', $petOwnerData->getPets()[1]->type);
        self::assertSame('Wouf', $petOwnerData->getPets()[1]->bark);
    }

    public function testAdderAndRemoverWithInstance(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $fish = new Fish();
        $fish->name = 'Nemo';
        $fish->type = 'fish';

        $petOwner = new PetOwner();
        $petOwner->addPet($fish);

        $petOwnerAsArray = [
            'pets' => [
                ['type' => 'cat', 'name' => 'Félix'],
                ['type' => 'dog', 'name' => 'Coco'],
            ],
        ];

        $this->autoMapper->map($petOwnerAsArray, $petOwner);

        self::assertIsArray($petOwner->getPets());
        self::assertCount(3, $petOwner->getPets());
        self::assertSame('Nemo', $petOwner->getPets()[0]->name);
        self::assertSame('fish', $petOwner->getPets()[0]->type);
        self::assertSame('Félix', $petOwner->getPets()[1]->name);
        self::assertSame('cat', $petOwner->getPets()[1]->type);
        self::assertSame('Coco', $petOwner->getPets()[2]->name);
        self::assertSame('dog', $petOwner->getPets()[2]->type);
    }

    public function testAdderAndRemoverWithNull(): void
    {
        $petOwner = [
            'pets' => [
                null,
                null,
            ],
        ];

        $petOwnerData = $this->autoMapper->map($petOwner, PetOwner::class);

        self::assertIsArray($petOwnerData->getPets());
        self::assertCount(0, $petOwnerData->getPets());
    }

    public function testAdderAndRemoverWithConstructorArguments(): void
    {
        $petOwner = [
            'pets' => [
                ['type' => 'cat', 'name' => 'Félix'],
            ],
        ];

        $petOwnerData = $this->autoMapper->map($petOwner, PetOwnerWithConstructorArguments::class);

        self::assertIsArray($petOwnerData->getPets());
        self::assertCount(1, $petOwnerData->getPets());
        self::assertSame('Félix', $petOwnerData->getPets()[0]->name);
        self::assertSame('cat', $petOwnerData->getPets()[0]->type);
    }

    public function testPartialConstructorWithTargetToPopulate(): void
    {
        $source = new Fixtures\User(1, 'Jack', 37);
        /** @var Fixtures\UserPartialConstructor $target */
        $target = $this->autoMapper->map($source, Fixtures\UserPartialConstructor::class);

        self::assertEquals(1, $target->getId());
        self::assertEquals('Jack', $target->name);
        self::assertEquals(37, $target->age);
    }

    public function testEnum(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        // enum source
        $address = new AddressWithEnum();
        $address->setType(AddressType::APARTMENT);
        /** @var array $addressData */
        $addressData = $this->autoMapper->map($address, 'array');
        $var = AddressType::APARTMENT; // only here for lower PHP version handling
        self::assertEquals($var->value, $addressData['type']);

        // enum target
        $data = ['type' => 'flat'];
        /** @var AddressWithEnum $address */
        $address = $this->autoMapper->map($data, AddressWithEnum::class);
        self::assertEquals(AddressType::FLAT, $address->getType());

        // both source & target are enums
        $address = new AddressWithEnum();
        $address->setType(AddressType::FLAT);
        /** @var AddressWithEnum $copyAddress */
        $copyAddress = $this->autoMapper->map($address, AddressWithEnum::class);
        self::assertEquals($address->getType(), $copyAddress->getType());
    }

    public function testTargetReadonlyClass(): void
    {
        $data = ['city' => 'Nantes'];
        $toPopulate = new Fixtures\AddressDTOSecondReadonlyClass('city', '67100');

        self::expectException(ReadOnlyTargetException::class);
        $this->autoMapper->map($data, $toPopulate);
    }

    public function testTargetReadonlyClassSkippedContext(): void
    {
        $data = ['city' => 'Nantes'];
        $toPopulate = new Fixtures\AddressDTOSecondReadonlyClass('city', '67100');

        $this->autoMapper->map($data, $toPopulate, [MapperContext::ALLOW_READONLY_TARGET_TO_POPULATE => true]);

        // value didn't changed because the object class is readonly, we can't change the value there
        self::assertEquals('city', $toPopulate->city);
    }

    public function testTargetReadonlyClassAllowed(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(true);

        $data = ['city' => 'Nantes'];
        $toPopulate = new AddressDTOReadonlyClass('city');

        $this->autoMapper->map($data, $toPopulate);

        // value didn't changed because the object class is readonly, we can't change the value there
        self::assertEquals('city', $toPopulate->city);
    }

    /**
     * @dataProvider provideReadonly
     */
    public function testReadonly(string $addressWithReadonlyClass): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(allowReadOnlyTargetToPopulate: true, mapPrivatePropertiesAndMethod: true);

        $address = new Address();
        $address->setCity('city');

        self::assertSame(
            ['city' => 'city'],
            $this->autoMapper->map(new $addressWithReadonlyClass('city'), 'array')
        );

        self::assertEquals(
            $address,
            $this->autoMapper->map(new $addressWithReadonlyClass('city'), Address::class)
        );

        self::assertEquals(
            new $addressWithReadonlyClass('city'),
            $this->autoMapper->map(['city' => 'city'], $addressWithReadonlyClass)
        );

        self::assertEquals(
            new $addressWithReadonlyClass('city'),
            $this->autoMapper->map($address, $addressWithReadonlyClass)
        );

        // assert that readonly property / class as a target object does not break automapping
        $address->setCity('another city');
        self::assertEquals(
            new $addressWithReadonlyClass('city'),
            $this->autoMapper->map($address, new $addressWithReadonlyClass('city'))
        );
    }

    public static function provideReadonly(): iterable
    {
        yield [AddressDTOWithReadonly::class];
        yield [AddressDTOWithReadonlyPromotedProperty::class];

        if (\PHP_VERSION_ID >= 80200) {
            yield [AddressDTOReadonlyClass::class];
        }
    }

    public function testDateTimeFormatCanBeConfiguredFromContext(): void
    {
        self::assertSame(
            ['dateTime' => '2021-01-01'],
            $this->autoMapper->map(
                new ObjectWithDateTime(new \DateTimeImmutable('2021-01-01 12:00:00')),
                'array',
                [MapperContext::DATETIME_FORMAT => 'Y-m-d']
            )
        );

        self::assertEquals(
            new ObjectWithDateTime(new \DateTimeImmutable('2023-01-24 00:00:00')),
            $this->autoMapper->map(
                ['dateTime' => '24-01-2023'],
                ObjectWithDateTime::class,
                [MapperContext::DATETIME_FORMAT => '!d-m-Y']
            )
        );
    }

    /**
     * @param class-string<HasDateTime|HasDateTimeWithNullValue|HasDateTimeImmutable|HasDateTimeImmutableWithNullValue|HasDateTimeInterfaceWithImmutableInstance|HasDateTimeInterfaceWithNullValue> $from
     * @param class-string<HasDateTime|HasDateTimeWithNullValue|HasDateTimeImmutable|HasDateTimeImmutableWithNullValue|HasDateTimeInterfaceWithImmutableInstance|HasDateTimeInterfaceWithNullValue> $to
     *
     * @dataProvider dateTimeMappingProvider
     */
    public function testDateTimeMapping(
        string $from,
        string $to,
        bool $isError,
    ): void {
        if ($isError) {
            $this->expectException(\TypeError::class);
        }

        $fromObject = $from::create();
        $toObject = $this->autoMapper->map($fromObject, $to);

        self::assertInstanceOf($to, $toObject);
        self::assertEquals($fromObject->getString(), $toObject->getString());
    }

    /**
     * @return iterable<array{0:HasDateTime|HasDateTimeWithNullValue|HasDateTimeImmutable|HasDateTimeImmutableWithNullValue|HasDateTimeInterfaceWithImmutableInstance|HasDateTimeInterfaceWithNullValue,1:HasDateTime|HasDateTimeWithNullValue|HasDateTimeImmutable|HasDateTimeImmutableWithNullValue|HasDateTimeInterfaceWithImmutableInstance|HasDateTimeInterfaceWithNullValue,2:bool}>
     */
    public function dateTimeMappingProvider(): iterable
    {
        $classes = [
            HasDateTime::class,
            HasDateTimeWithNullValue::class,
            HasDateTimeImmutable::class,
            HasDateTimeImmutableWithNullValue::class,
            HasDateTimeInterfaceWithImmutableInstance::class,
            HasDateTimeInterfaceWithMutableInstance::class,
            HasDateTimeInterfaceWithNullValue::class,
        ];

        foreach ($classes as $from) {
            foreach ($classes as $to) {
                $fromIsNullable = str_contains($from, 'NullValue');
                $toIsNullable = str_contains($to, 'NullValue');
                $isError = $fromIsNullable && !$toIsNullable;
                yield "$from to $to" => [$from, $to, $isError];
            }
        }
    }

    public function testMapToContextAttribute(): void
    {
        self::assertSame(
            [
                'propertyWithDefaultValue' => 'foo',
                'value' => 'foo_bar_baz',
                'virtualProperty' => 'foo_bar_baz',
            ],
            $this->autoMapper->map(
                new ClassWithMapToContextAttribute('bar'),
                'array',
                [MapperContext::MAP_TO_ACCESSOR_PARAMETER => ['suffix' => 'baz', 'prefix' => 'foo']]
            )
        );
    }

    public function testMapClassWithPrivateProperty(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        self::assertSame(
            ['bar' => 'bar', 'foo' => 'foo'],
            $this->autoMapper->map(new ClassWithPrivateProperty('foo'), 'array')
        );
        self::assertEquals(
            new ClassWithPrivateProperty('foo'),
            $this->autoMapper->map(['foo' => 'foo'], ClassWithPrivateProperty::class)
        );
    }

    /**
     * Generated mapper will be different from what "testMapClassWithPrivateProperty" generates,
     * hence the duplicated class, to avoid any conflict with autloading.
     */
    public function testItCanDisablePrivatePropertiesMapping(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'DontMapPrivate_');

        self::assertSame(
            [],
            $this->autoMapper->map(new ClassWithPrivateProperty('foo'), 'array')
        );
    }

    public function testItCanMapFromArrayWithMissingNullableProperty(): void
    {
        self::assertEquals(
            new ClassWithNullablePropertyInConstructor(foo: 1),
            $this->autoMapper->map(['foo' => 1], ClassWithNullablePropertyInConstructor::class)
        );
    }

    public function testNoErrorWithUninitializedProperty(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        self::assertSame(
            ['bar' => 'bar'],
            $this->autoMapper->map(new Uninitialized(), 'array', [MapperContext::SKIP_UNINITIALIZED_VALUES => true])
        );
    }

    public function testMapWithForcedTimeZone(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        /** @var HasDateTimeImmutable $utc */
        $utc = $this->autoMapper->map(
            ['dateTime' => '2024-03-11 17:00:00'],
            HasDateTimeImmutable::class,
            [MapperContext::DATETIME_FORMAT => 'Y-m-d H:i:s', MapperContext::DATETIME_FORCE_TIMEZONE => 'Europe/Paris']
        );

        self::assertEquals(new \DateTimeZone('Europe/Paris'), $utc->dateTime->getTimezone());
    }

    public function testAutoMappingGenerator(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);
        $foo = new FooGenerator();

        /** @var Fixtures\BarGenerator $bar */
        $bar = $this->autoMapper->map($foo, Fixtures\BarGenerator::class);

        // Test mapping to class
        self::assertInstanceOf(Fixtures\BarGenerator::class, $bar);

        self::assertSame([1, 2, 3, 'foo' => 'bar'], $bar->generator);
        self::assertSame([1, 2, 3], $bar->array);

        // Test mapping to array
        $data = $this->autoMapper->map($foo, 'array');

        self::assertSame([1, 2, 3, 'foo' => 'bar'], $data['generator']);
        self::assertSame([1, 2, 3], $data['array']);

        // Test mapping to stdClass
        $data = $this->autoMapper->map($foo, \stdClass::class);

        self::assertSame([1, 2, 3, 'foo' => 'bar'], $data->generator);
        self::assertSame([1, 2, 3], $data->array);
    }

    public function testMultipleArray(): void
    {
        $now = new \DateTimeImmutable();
        $userDto = new Fixtures\UserDTO();
        $userDto->times = [$now, $now];

        $user = $this->autoMapper->map($userDto, 'array');

        self::assertSame([$now->format(\DateTimeInterface::RFC3339), $now->format(\DateTimeInterface::RFC3339)], $user['times']);

        $userDto = new Fixtures\UserDTO();
        $userDto->times = [0, 1];

        $user = $this->autoMapper->map($userDto, 'array');

        self::assertSame([0, 1], $user['times']);
    }

    public function testDateTimeFromString(): void
    {
        $now = new \DateTimeImmutable();
        $data = ['dateTime' => $now->format(\DateTimeInterface::RFC3339)];
        $object = $this->autoMapper->map($data, HasDateTime::class);

        self::assertEquals($now->format(\DateTimeInterface::RFC3339), $object->dateTime->format(\DateTimeInterface::RFC3339));
    }

    public function testRealClassName(): void
    {
        require_once __DIR__ . '/Fixtures/proxies.php';

        $proxy = new \Proxies\__CG__\AutoMapper\Tests\Fixtures\Proxy();
        $proxy->foo = 'bar';

        $mapper = $this->autoMapper->getMapper($proxy::class, 'array');

        self::assertNotEquals('Mapper_Proxies___CG___AutoMapper_Tests_Fixtures_Proxy', $mapper::class);
        self::assertEquals('Mapper_AutoMapper_Tests_Fixtures_Proxy_array', $mapper::class);

        $proxy = new \MongoDBODMProxies\__PM__\AutoMapper\Tests\Fixtures\Proxy\Generated();
        $proxy->foo = 'bar';

        $mapper = $this->autoMapper->getMapper($proxy::class, 'array');

        self::assertNotEquals('Mapper_MongoDBODMProxies___PM___AutoMapper_Tests_Fixtures_Proxy_Generated_array', $mapper::class);
        self::assertEquals('Mapper_AutoMapper_Tests_Fixtures_Proxy_array', $mapper::class);
    }

    public function testDiscriminatorMapAndInterface(): void
    {
        if (!class_exists(ClassDiscriminatorFromClassMetadata::class)) {
            self::markTestSkipped('Symfony Serializer is required to run this test.');
        }

        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $typeA = new Fixtures\DiscriminatorMapAndInterface\TypeA('my name');
        $something = new Fixtures\DiscriminatorMapAndInterface\Something($typeA);

        $mapped = $this->autoMapper->map($something, 'array');

        $expected = [
            'myInterface' => [
                'type' => 'type_a',
                'name' => 'my name',
            ],
        ];
        self::assertSame($expected, $mapped);
    }

    public function testDiscriminatorMapAndInterface2(): void
    {
        if (!class_exists(ClassDiscriminatorFromClassMetadata::class)) {
            self::markTestSkipped('Symfony Serializer is required to run this test.');
        }

        $this->buildAutoMapper(classPrefix: 'Discriminator2');

        $something = [
            'myInterface' => [
                'type' => 'type_a',
                'name' => 'my name',
            ],
        ];

        $mapped = $this->autoMapper->map($something, Fixtures\DiscriminatorMapAndInterface\Something::class);

        self::assertInstanceOf(Fixtures\DiscriminatorMapAndInterface\Something::class, $mapped);
        self::assertInstanceOf(Fixtures\DiscriminatorMapAndInterface\TypeA::class, $mapped->myInterface);
        self::assertSame('my name', $mapped->myInterface->name);
    }

    public function testDiscriminantToArray(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $dog = new Dog();
        $dog->bark = 'Wouf';
        $dog->type = 'dog';
        $dog->name = 'Coco';

        $petOwner = new PetOwner();
        $petOwner->addPet($dog);

        $dog->owner = $petOwner;

        $petOwnerData = $this->autoMapper->map($petOwner, 'array');

        self::assertIsArray($petOwnerData['pets']);
        self::assertCount(1, $petOwnerData['pets']);
        self::assertSame('Coco', $petOwnerData['pets'][0]['name']);
        self::assertSame('dog', $petOwnerData['pets'][0]['type']);
        self::assertSame('Wouf', $petOwnerData['pets'][0]['bark']);
    }

    public function testMapCollectionFromArray(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

        $users = [
            [
                'id' => 1,
                'address' => [
                    'city' => 'Toulon',
                ],
                'createdAt' => '1987-04-30T06:00:00Z',
            ],
            [
                'id' => 2,
                'address' => [
                    'city' => 'Nantes',
                ],
                'createdAt' => '1991-10-01T06:00:00Z',
            ],
        ];

        /** @var array<Fixtures\UserDTO> $userDtos */
        $userDtos = $this->autoMapper->mapCollection($users, Fixtures\UserDTO::class);
        self::assertCount(2, $userDtos);
        self::assertEquals(1, $userDtos[0]->id);
        self::assertInstanceOf(AddressDTO::class, $userDtos[0]->address);
        self::assertSame('Toulon', $userDtos[0]->address->city);
        self::assertInstanceOf(\DateTimeInterface::class, $userDtos[0]->createdAt);
        self::assertEquals(1987, $userDtos[0]->createdAt->format('Y'));
        self::assertEquals(2, $userDtos[1]->id);
        self::assertInstanceOf(AddressDTO::class, $userDtos[1]->address);
        self::assertSame('Nantes', $userDtos[1]->address->city);
        self::assertInstanceOf(\DateTimeInterface::class, $userDtos[1]->createdAt);
        self::assertEquals(1991, $userDtos[1]->createdAt->format('Y'));
    }

    public function testMapCollectionFromArrayCustomDateTime(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper(classPrefix: 'CustomDateTime_', dateTimeFormat: 'U');

        $customFormat = 'U';
        $users = [
            [
                'id' => 1,
                'address' => [
                    'city' => 'Toulon',
                ],
                'createdAt' => \DateTime::createFromFormat(\DateTime::RFC3339, '1987-04-30T06:00:00Z')->format($customFormat),
            ],
            [
                'id' => 2,
                'address' => [
                    'city' => 'Nantes',
                ],
                'createdAt' => \DateTime::createFromFormat(\DateTime::RFC3339, '1991-10-01T06:00:00Z')->format($customFormat),
            ],
        ];

        /** @var array<Fixtures\UserDTO> $userDtos */
        $userDtos = $this->autoMapper->mapCollection($users, Fixtures\UserDTO::class);
        self::assertCount(2, $userDtos);

        self::assertInstanceOf(Fixtures\UserDTO::class, $userDtos[0]);
        self::assertEquals(\DateTime::createFromFormat(\DateTime::RFC3339, '1987-04-30T06:00:00Z')->format($customFormat), $userDtos[0]->createdAt->format($customFormat));
        self::assertInstanceOf(Fixtures\UserDTO::class, $userDtos[1]);
        self::assertEquals(\DateTime::createFromFormat(\DateTime::RFC3339, '1991-10-01T06:00:00Z')->format($customFormat), $userDtos[1]->createdAt->format($customFormat));
    }

    public function testMapCollectionToArray(): void
    {
        $users = [];
        $address = new Address();
        $address->setCity('Toulon');
        $user = new Fixtures\User(1, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;
        $users[] = $user;
        $address = new Address();
        $address->setCity('Nantes');
        $user = new Fixtures\User(10, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;
        $users[] = $user;

        $userDatas = $this->autoMapper->mapCollection($users, 'array');

        self::assertIsArray($userDatas);
        self::assertIsArray($userDatas[0]);
        self::assertIsArray($userDatas[1]);
        self::assertEquals(1, $userDatas[0]['id']);
        self::assertEquals(10, $userDatas[1]['id']);
        self::assertIsArray($userDatas[0]['address']);
        self::assertIsString($userDatas[0]['createdAt']);
        self::assertIsArray($userDatas[1]['address']);
        self::assertIsString($userDatas[1]['createdAt']);
    }

    /**
     * @dataProvider provideAutoMapperFixturesTests
     */
    public function testAutoMapperFixtures(string $mapFile, string $directory): void
    {
        try {
            $targets = require $mapFile;
        } catch (\Throwable $e) {
            throw new \LogicException(sprintf('Unable to load map file "%s".', $mapFile), 0, $e);
        }

        if (1 === $targets) {
            throw new \LogicException(sprintf('The map file "%s" does not return a value.', $mapFile));
        }

        if (!$targets instanceof \Generator) {
            $targets = [$targets];
        }

        foreach ($targets as $key => $target) {
            $dump = $this->getDump($target);

            if (0 === $key) {
                $expectedFile = sprintf('%s/expected.data', $directory);
            } else {
                $expectedFile = sprintf('%s/expected.%s.data', $directory, $key);
            }

            if ($_SERVER['UPDATE_FIXTURES'] ?? false) {
                file_put_contents($expectedFile, $dump);
            }

            if (!file_exists($expectedFile)) {
                throw new \LogicException(sprintf('The expected file "%s" does not exist.', $expectedFile));
            }

            $expected = trim(file_get_contents($expectedFile));

            $this->assertSame($expected, $dump, sprintf('The dump of the map file "%s" is not as expected.', $key));
        }
    }

    public static function provideAutoMapperFixturesTests(): iterable
    {
        $directories = (new Finder())
            ->in(__DIR__ . '/AutoMapperTest')
            ->depth(0)
            ->directories()
        ;

        foreach ($directories as $directory) {
            $mapFile = $directory->getRealPath() . '/map.php';

            if (!file_exists($mapFile)) {
                throw new \LogicException(sprintf('The map file "%s" does not exist.', $mapFile));
            }

            yield $directory->getBasename() => [$mapFile, $directory->getRealPath()];
        }
    }
}
