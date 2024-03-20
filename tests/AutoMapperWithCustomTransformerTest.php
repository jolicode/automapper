<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\Tests\Fixtures\Address;
use AutoMapper\Tests\Fixtures\AddressDTO;
use AutoMapper\Tests\Fixtures\BirthDateDateTime;
use AutoMapper\Tests\Fixtures\BirthDateExploded;
use AutoMapper\Tests\Fixtures\CityFoo;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FooDependency;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FromSourceCustomModelTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FromSourceCustomPropertyTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FromTargetCustomModelTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\FromTargetCustomPropertyTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\PrioritizedFromSourcePropertyPriorityTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\SourceTargetCustomModelTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\SourceTargetCustomPropertyTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\SourceTargetMultiFieldsCustomPropertyTransformer;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\TransformerWithDependency;
use AutoMapper\Tests\Fixtures\User;
use AutoMapper\Tests\Fixtures\UserDTO;

class AutoMapperWithCustomTransformerTest extends AutoMapperBaseTest
{
    public function testFromSourceCanUseCustomTransformer(): void
    {
        $this->buildAutoMapper(classPrefix: 'FromSourceCustomTransformer_', propertyTransformers: [
            new FromSourceCustomModelTransformer(),
            new FromSourceCustomPropertyTransformer(),
        ]);

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
        $this->buildAutoMapper(classPrefix: 'PrioritizedCustomTransformer_', propertyTransformers: [
            new FromSourceCustomModelTransformer(),
            new PrioritizedFromSourcePropertyPriorityTransformer(),
        ]);

        self::assertSame(
            'address with city "city DTO"',
            $this->autoMapper->map(self::createUserDTO(), 'array')['address']
        );
    }

    public function testFromTargetCanUseCustomTransformer(): void
    {
        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true, classPrefix: 'FromTargetCustomTransformer_', propertyTransformers: [
            new FromTargetCustomModelTransformer(),
            new FromTargetCustomPropertyTransformer(),
        ]);

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
        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true, classPrefix: 'SourceTargetCustomTransformer_', propertyTransformers: [
            new SourceTargetCustomModelTransformer(),
            new SourceTargetCustomPropertyTransformer(),
        ]);

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

    public function testFromSourceToTargetMultipleFieldsTransformation(): void
    {
        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true, classPrefix: 'SourceTargetMultiFieldsCustomPropertyTransformer_', propertyTransformers: [
            new SourceTargetMultiFieldsCustomPropertyTransformer(),
        ]);

        $birthDateDateTime = $this->autoMapper->map(
            new BirthDateExploded(year: 1985, month: 07, day: 01),
            BirthDateDateTime::class
        );

        self::assertSame('1985-07-01', $birthDateDateTime->date->format('Y-m-d'));
    }

    public function testCustomTransformerWithDependency(): void
    {
        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true, classPrefix: 'TransformerWithDependency');
        $this->buildAutoMapper(mapPrivatePropertiesAndMethod: true, classPrefix: 'TransformerWithDependency', propertyTransformers: [
            new TransformerWithDependency(new FooDependency()),
        ]);

        $source = new CityFoo();
        $source->name = 'foo';

        self::assertSame(['name' => 'bar'], $this->autoMapper->map(
            $source,
            'array'
        ));
    }

    private static function createUserDTO(?string $name = null, ?string $city = null): UserDTO
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

    private static function createUser(?string $name = null, ?string $city = null): User
    {
        $user = new User(666, $name ?? 'name', 666);
        $address = new Address();
        $address->setCity($city ?? 'city');
        $user->address = $address;

        return $user;
    }
}
