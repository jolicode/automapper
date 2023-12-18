<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Extractor\SourceTargetMappingExtractor;
use AutoMapper\MapperGeneratorMetadataFactory;
use AutoMapper\MapperGeneratorMetadataFactoryInterface;
use AutoMapper\Transformer\ArrayTransformerFactory;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Transformer\DateTimeTransformerFactory;
use AutoMapper\Transformer\MultipleTransformerFactory;
use AutoMapper\Transformer\NullableTransformerFactory;
use AutoMapper\Transformer\ObjectTransformerFactory;
use AutoMapper\Transformer\UniqueTypeTransformerFactory;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class MapperGeneratorMetadataFactoryTest extends AutoMapperBaseTest
{
    protected MapperGeneratorMetadataFactoryInterface $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $customTransformerRegistry = new CustomTransformersRegistry();
        $reflectionExtractor = new ReflectionExtractor(null, null, null, true, ReflectionExtractor::ALLOW_PUBLIC | ReflectionExtractor::ALLOW_PROTECTED | ReflectionExtractor::ALLOW_PRIVATE);

        $phpDocExtractor = new PhpDocExtractor();
        $propertyInfoExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpDocExtractor, $reflectionExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor]
        );

        $transformerFactory = new ChainTransformerFactory();
        $sourceTargetMappingExtractor = new SourceTargetMappingExtractor(
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $transformerFactory,
            $customTransformerRegistry,
            $classMetadataFactory
        );

        $fromTargetMappingExtractor = new FromTargetMappingExtractor(
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $transformerFactory,
            $customTransformerRegistry,
            $classMetadataFactory
        );

        $fromSourceMappingExtractor = new FromSourceMappingExtractor(
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $transformerFactory,
            $customTransformerRegistry,
            $classMetadataFactory
        );

        $this->factory = new MapperGeneratorMetadataFactory(
            $sourceTargetMappingExtractor,
            $fromSourceMappingExtractor,
            $fromTargetMappingExtractor
        );

        $transformerFactory->addTransformerFactory(new MultipleTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new NullableTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new UniqueTypeTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new DateTimeTransformerFactory());
        $transformerFactory->addTransformerFactory(new BuiltinTransformerFactory());
        $transformerFactory->addTransformerFactory(new ArrayTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new ObjectTransformerFactory($this->autoMapper));
    }

    public function testCreateObjectToArray(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);

        $metadata = $this->factory->create($this->autoMapper, Fixtures\User::class, 'array');
        self::assertFalse($metadata->hasConstructor());
        self::assertTrue($metadata->shouldCheckAttributes());
        self::assertFalse($metadata->isTargetCloneable());
        self::assertEquals(Fixtures\User::class, $metadata->getSource());
        self::assertEquals('array', $metadata->getTarget());
        self::assertCount(\count($userReflection->getProperties()), $metadata->getPropertiesMapping());
        self::assertInstanceOf(PropertyMapping::class, $metadata->getPropertyMapping('id'));
        self::assertInstanceOf(PropertyMapping::class, $metadata->getPropertyMapping('name'));
        self::assertInstanceOf(PropertyMapping::class, $metadata->getPropertyMapping('email'));
    }

    public function testCreateArrayToObject(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);

        $metadata = $this->factory->create($this->autoMapper, 'array', Fixtures\User::class);
        self::assertTrue($metadata->hasConstructor());
        self::assertTrue($metadata->shouldCheckAttributes());
        self::assertTrue($metadata->isTargetCloneable());
        self::assertEquals('array', $metadata->getSource());
        self::assertEquals(Fixtures\User::class, $metadata->getTarget());
        self::assertCount(\count($userReflection->getProperties()), $metadata->getPropertiesMapping());
        self::assertInstanceOf(PropertyMapping::class, $metadata->getPropertyMapping('id'));
        self::assertInstanceOf(PropertyMapping::class, $metadata->getPropertyMapping('name'));
        self::assertInstanceOf(PropertyMapping::class, $metadata->getPropertyMapping('email'));
    }

    public function testCreateWithBothObjects(): void
    {
        $metadata = $this->factory->create($this->autoMapper, Fixtures\UserConstructorDTO::class, Fixtures\User::class);
        self::assertTrue($metadata->hasConstructor());
        self::assertTrue($metadata->shouldCheckAttributes());
        self::assertTrue($metadata->isTargetCloneable());
        self::assertEquals(Fixtures\UserConstructorDTO::class, $metadata->getSource());
        self::assertEquals(Fixtures\User::class, $metadata->getTarget());
        self::assertInstanceOf(PropertyMapping::class, $metadata->getPropertyMapping('id'));
        self::assertInstanceOf(PropertyMapping::class, $metadata->getPropertyMapping('name'));
        self::assertNull($metadata->getPropertyMapping('email'));
        self::assertFalse($metadata->isTargetReadOnlyClass());
    }

    public function testHasNotConstructor(): void
    {
        $metadata = $this->factory->create($this->autoMapper, 'array', Fixtures\UserDTO::class);

        self::assertFalse($metadata->hasConstructor());
    }

    /**
     * @requires PHP 8.2
     */
    public function testTargetIsReadOnlyClass(): void
    {
        $metadata = $this->factory->create($this->autoMapper, 'array', Fixtures\AddressDTOReadonlyClass::class);

        self::assertEquals(Fixtures\AddressDTOReadonlyClass::class, $metadata->getTarget());
        self::assertTrue($metadata->isTargetReadOnlyClass());
    }
}
