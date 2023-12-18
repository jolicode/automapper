<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\Tests\Fixtures\Address;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FromSourceCustomModelTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FromSourceCustomPropertyTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FromTargetCustomModelTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FromTargetCustomPropertyTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\PrioritizedFromSourceCustomPriorityTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\SourceTargetCustomModelTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\SourceTargetCustomPropertyTransformer;
use AutoMapper\Tests\Fixtures\User;
use AutoMapper\Tests\Fixtures\UserDTO;

class AutoMapperWithCustomTransformerTest extends AutoMapperBaseTest
{
    public function testFromSourceCanUseCustomTransformer(): void
    {
        $this->buildAutoMapper(classPrefix: 'FromSourceCustomTransformer_');

        $this->autoMapper->bindCustomTransformer(new FromSourceCustomModelTransformer());
        $this->autoMapper->bindCustomTransformer(new FromSourceCustomPropertyTransformer());

        $mapped = $this->autoMapper->map(self::createUserDTO(), 'array');
        self::assertSame(
            'name DTO set by custom property transformer',
            $mapped['name']
        );

        self::assertSame(
            [
                'city' => 'city DTO set by custom model transformer',
                'street' => 'street set by custom model transformer',
            ],
            $mapped['address']
        );
    }

    public function testPrioritizedCustomTransformer(): void
    {
        $this->buildAutoMapper(classPrefix: 'PrioritizedCustomTransformer_');

        $this->autoMapper->bindCustomTransformer(new FromSourceCustomModelTransformer());
        $this->autoMapper->bindCustomTransformer(new PrioritizedFromSourceCustomPriorityTransformer());

        self::assertSame(
            'address with city "city DTO"',
            $this->autoMapper->map(self::createUserDTO(), 'array')['address']
        );
    }

    public function testFromTargetCanUseCustomTransformer(): void
    {
        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true, classPrefix: 'FromTargetCustomTransformer_');

        $this->autoMapper->bindCustomTransformer(new FromTargetCustomModelTransformer());
        $this->autoMapper->bindCustomTransformer(new FromTargetCustomPropertyTransformer());

        self::assertEquals(
            self::createUserDTO('name DTO from custom property transformer', 'city DTO from custom model transformer'),
            $this->autoMapper->map([
                'id' => 666,
                'name' => 'name DTO',
                'age' => 666,
                'address' => [
                    'city' => 'city DTO',
                    'street' => 'street',
                ],
            ], UserDTO::class)
        );
    }

    /**
     * @dataProvider providerFromSourceToTargetCanUseCustomTransformer
     */
    public function testFromSourceToTargetCanUseCustomTransformer(string|object $target): void
    {
        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true, classPrefix: 'SourceTargetCustomTransformer_');

        $this->autoMapper->bindCustomTransformer(new SourceTargetCustomModelTransformer());
        $this->autoMapper->bindCustomTransformer(new SourceTargetCustomPropertyTransformer());

        $mappedUser = $this->autoMapper->map(self::createUserDTO(), $target);

        $expectedAddress = new Address();
        $expectedAddress->setCity('city DTO from custom model transformer');
        self::assertEquals($expectedAddress, $mappedUser->address);
        self::assertEquals('name DTO from custom property transformer', $mappedUser->name);
    }

    public function providerFromSourceToTargetCanUseCustomTransformer(): iterable
    {
        yield 'class name' => [User::class];
        yield 'object' => [self::createUser()];
    }

    private static function createUserDTO(string|null $name = null, string|null $city = null): UserDTO
    {
        $user = new UserDTO();
        $user->id = 666;
        $user->age = 666;
        $user->setName($name ?? 'name DTO');
        $address = new AddressDTO();
        $address->city = $city ?? 'city DTO';
        $user->address = $address;

        return $user;
    }

    private static function createUser(string|null $name = null, string|null $city = null): User
    {
        $user = new User(666, $name ?? 'name', 666);
        $address = new Address();
        $address->setCity($city ?? 'city');
        $user->address = $address;

        return $user;
    }
}
