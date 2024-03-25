<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle;

use AutoMapper\AutoMapperInterface;
use AutoMapper\MapperContext;
use AutoMapper\Symfony\Bundle\CacheWarmup\CacheWarmer;
use AutoMapper\Tests\Bundle\Resources\App\Entity\AddressDTO;
use AutoMapper\Tests\Bundle\Resources\App\Entity\ClassWithMapToContextAttribute;
use AutoMapper\Tests\Bundle\Resources\App\Entity\ClassWithPrivateProperty;
use AutoMapper\Tests\Bundle\Resources\App\Entity\DTOWithEnum;
use AutoMapper\Tests\Bundle\Resources\App\Entity\FooMapTo;
use AutoMapper\Tests\Bundle\Resources\App\Entity\SomeEnum;
use AutoMapper\Tests\Bundle\Resources\App\Entity\User;
use AutoMapper\Tests\Bundle\Resources\App\Entity\UserDTO;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ServiceInstantiationTest extends WebTestCase
{
    protected function setUp(): void
    {
        static::$class = null;
        $_SERVER['KERNEL_DIR'] = __DIR__ . '/Resources/App';
        $_SERVER['KERNEL_CLASS'] = 'AutoMapper\Tests\Bundle\Resources\App\AppKernel';
        $_SERVER['APP_DEBUG'] = false;

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
        $service = static::$kernel->getContainer()->get(CacheWarmer::class);
        $service->warmUp(__DIR__ . '/Resources/var/cache/test');

        self::assertFileExists(__DIR__ . '/Resources/var/cache/test/automapper/Symfony_Mapper_AutoMapper_Tests_Bundle_Resources_App_Entity_NestedObject_array.php');
        self::assertFileExists(__DIR__ . '/Resources/var/cache/test/automapper/Symfony_Mapper_AutoMapper_Tests_Bundle_Resources_App_Entity_User_array.php');
        self::assertFileExists(__DIR__ . '/Resources/var/cache/test/automapper/Symfony_Mapper_AutoMapper_Tests_Bundle_Resources_App_Entity_AddressDTO_array.php');
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

        $pet = $autoMapper->map($data, Resources\App\Entity\Pet::class);
        self::assertInstanceOf(Resources\App\Entity\Cat::class, $pet);
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

    public function testMapTo()
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        $autoMapper = $container->get(AutoMapperInterface::class);

        $foo = new FooMapTo('foo');
        $bar = $autoMapper->map($foo, 'array');

        $this->assertIsArray($bar);
        $this->assertArrayNotHasKey('bar', $bar);
        $this->assertArrayNotHasKey('a', $bar);
        $this->assertSame('foo', $bar['baz']);
        $this->assertSame('foo', $bar['foo']);
        $this->assertSame('transformFromIsCallable_foo', $bar['transformFromIsCallable']);
        $this->assertSame('transformFromStringInstance_foo', $bar['transformFromStringInstance']);
        $this->assertSame('transformFromStringStatic_foo', $bar['transformFromStringStatic']);
        $this->assertSame('if', $bar['if']);
        $this->assertSame('if', $bar['ifCallableStatic']);
        $this->assertSame('if', $bar['ifCallable']);
        $this->assertSame('if', $bar['ifCallableOther']);
        $this->assertSame('transformed', $bar['transformFromExpressionLanguage']);
        $this->assertSame('foo', $bar['transformWithExpressionFunction']);
    }
}
