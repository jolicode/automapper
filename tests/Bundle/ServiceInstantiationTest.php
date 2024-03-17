<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle;

use AutoMapper\AutoMapperInterface;
use AutoMapper\MapperContext;
use AutoMapper\Tests\Bundle\Fixtures\AddressDTO;
use AutoMapper\Tests\Bundle\Fixtures\ClassWithMapToContextAttribute;
use AutoMapper\Tests\Bundle\Fixtures\ClassWithPrivateProperty;
use AutoMapper\Tests\Bundle\Fixtures\DTOWithEnum;
use AutoMapper\Tests\Bundle\Fixtures\SomeEnum;
use AutoMapper\Tests\Bundle\Fixtures\User;
use AutoMapper\Tests\Bundle\Fixtures\UserDTO;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ServiceInstantiationTest extends WebTestCase
{
    protected function setUp(): void
    {
        static::$class = null;
        $_SERVER['KERNEL_DIR'] = __DIR__ . '/Resources/App';
        $_SERVER['KERNEL_CLASS'] = 'DummyApp\AppKernel';

        (new Filesystem())->remove(__DIR__ . '/Resources/var/cache/test');
    }

    /**
     * This method needs to be the first in this test class, more details about why here: https://github.com/janephp/janephp/pull/734#discussion_r1247921885.
     *
     * @see Resources/App/config.yml
     */
    public function testWarmup(): void
    {
        static::bootKernel();

        self::assertFileExists(__DIR__ . '/Resources/var/cache/test/automapper/Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_NestedObject_array.php');
        self::assertFileExists(__DIR__ . '/Resources/var/cache/test/automapper/Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_User_array.php');
        self::assertFileExists(__DIR__ . '/Resources/var/cache/test/automapper/Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_AddressDTO_array.php');

        self::assertInstanceOf(\Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_NestedObject_array::class, new \Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_NestedObject_array());
        self::assertInstanceOf(\Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_User_array::class, new \Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_User_array());
        self::assertInstanceOf(\Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_AddressDTO_array::class, new \Symfony_Mapper_AutoMapper_Tests_Bundle_Fixtures_AddressDTO_array());
    }

    public function testAutoMapper(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has(AutoMapperInterface::class));
        $autoMapper = $container->get(AutoMapperInterface::class);

        $this->assertInstanceOf(AutoMapperInterface::class, $autoMapper);

        $address = new AddressDTO();
        $address->city = 'Toulon';
        $user = new User(1, 'yolo', '13');
        $user->address = $address;
        $user->addresses[] = $address;

        /** @var UserDTO $userDto */
        $userDto = $autoMapper->map($user, UserDTO::class);

        self::assertInstanceOf(UserDTO::class, $userDto);
        self::assertSame(1, $userDto->id);
        self::assertSame('yolo', $userDto->getName());
        self::assertSame(13, $userDto->age);
        self::assertNull($userDto->email);
        self::assertInstanceOf(AddressDTO::class, $userDto->address);
        self::assertCount(1, $userDto->addresses);
        self::assertInstanceOf(AddressDTO::class, $userDto->addresses[0]);
        self::assertSame('Toulon', $userDto->address->city);
        self::assertSame('Toulon', $userDto->addresses[0]->city);

        $userArray = $autoMapper->map($user, 'array');
        self::assertIsArray($userArray);
        self::assertArrayHasKey('@id', $userArray);
        self::assertSame(1, $userArray['@id']);
    }

    public function testDiscriminator(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has(AutoMapperInterface::class));
        $autoMapper = $container->get(AutoMapperInterface::class);
        $this->assertInstanceOf(AutoMapperInterface::class, $autoMapper);

        $data = [
            'type' => 'cat',
        ];

        $pet = $autoMapper->map($data, Fixtures\Pet::class);
        self::assertInstanceOf(Fixtures\Cat::class, $pet);
    }

    public function testItCanMapEnums(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $autoMapper = $container->get(AutoMapperInterface::class);

        $dto = new DTOWithEnum();
        $dto->enum = SomeEnum::FOO;
        self::assertSame(['enum' => 'foo'], $autoMapper->map($dto, 'array'));
    }

    /**
     * This test validates that PropertyInfoPass is correctly applied.
     */
    public function testMapClassWithPrivateProperty(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $autoMapper = $container->get(AutoMapperInterface::class);

        self::assertEquals(
            new ClassWithPrivateProperty('bar'),
            $autoMapper->map(['foo' => 'bar'], ClassWithPrivateProperty::class)
        );
    }

    /**
     * We need to test that the mapToContext attribute is correctly used,
     * because this behavior is dependent of the dependency injection.
     */
    public function testMapToContextAttribute(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $autoMapper = $container->get(AutoMapperInterface::class);

        self::assertSame(
            [
                'value' => 'foo_bar_baz',
                'virtualProperty' => 'foo_bar_baz',
                'propertyWithDefaultValue' => 'foo',
            ],
            $autoMapper->map(
                new ClassWithMapToContextAttribute('bar'),
                'array',
                [MapperContext::MAP_TO_ACCESSOR_PARAMETER => ['suffix' => 'baz', 'prefix' => 'foo']]
            )
        );
    }
}
