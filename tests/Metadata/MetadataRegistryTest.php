<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Metadata;

use AutoMapper\Configuration;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Extractor\SourceTargetMappingExtractor;
use AutoMapper\Metadata\GeneratorMetadata;
use AutoMapper\Metadata\MetadataRegistry;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Tests\AutoMapperBaseTest;
use AutoMapper\Tests\Fixtures;
use AutoMapper\Transformer\ArrayTransformerFactory;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\DateTimeTransformerFactory;
use AutoMapper\Transformer\MultipleTransformerFactory;
use AutoMapper\Transformer\NullableTransformerFactory;
use AutoMapper\Transformer\ObjectTransformerFactory;
use AutoMapper\Transformer\UniqueTypeTransformerFactory;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class MetadataRegistryTest extends AutoMapperBaseTest
{
    protected MetadataRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration = new Configuration();

        if (!class_exists(AttributeLoader::class)) {
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader());
        } else {
            $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        }

        $reflectionExtractor = new ReflectionExtractor(null, null, null, true, ReflectionExtractor::ALLOW_PUBLIC | ReflectionExtractor::ALLOW_PROTECTED | ReflectionExtractor::ALLOW_PRIVATE);

        $phpStanExtractor = new PhpStanExtractor();
        $propertyInfoExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpStanExtractor, $reflectionExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor]
        );

        $transformerFactory = new ChainTransformerFactory([
            new MultipleTransformerFactory(),
            new NullableTransformerFactory(),
            new UniqueTypeTransformerFactory(),
            new DateTimeTransformerFactory(),
            new BuiltinTransformerFactory(),
            new ArrayTransformerFactory(),
            new ObjectTransformerFactory(),
        ]);

        $sourceTargetMappingExtractor = new SourceTargetMappingExtractor(
            $configuration,
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $classMetadataFactory
        );

        $fromTargetMappingExtractor = new FromTargetMappingExtractor(
            $configuration,
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $classMetadataFactory
        );

        $fromSourceMappingExtractor = new FromSourceMappingExtractor(
            $configuration,
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $classMetadataFactory
        );

        $this->registry = new MetadataRegistry(
            $configuration,
            $sourceTargetMappingExtractor,
            $fromSourceMappingExtractor,
            $fromTargetMappingExtractor,
            $transformerFactory,
        );
    }

    public function testCreateObjectToArray(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);

        $metadata = $this->registry->getGeneratorMetadata(Fixtures\User::class, 'array');
        self::assertFalse($metadata->hasConstructor());
        self::assertFalse($metadata->isTargetCloneable());
        self::assertEquals(Fixtures\User::class, $metadata->mapperMetadata->source);
        self::assertEquals('array', $metadata->mapperMetadata->target);
        self::assertCount(\count($userReflection->getProperties()), $metadata->propertiesMetadata);
    }

    public function testCreateArrayToObject(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);

        $metadata = $this->registry->getGeneratorMetadata('array', Fixtures\User::class);
        self::assertTrue($metadata->hasConstructor());
        self::assertTrue($metadata->isTargetCloneable());
        self::assertEquals('array', $metadata->mapperMetadata->source);
        self::assertEquals(Fixtures\User::class, $metadata->mapperMetadata->target);
        self::assertCount(\count($userReflection->getProperties()), $metadata->propertiesMetadata);
        self::assertInstanceOf(PropertyMetadata::class, $this->getPropertyMetadata($metadata, 'id'));
        self::assertInstanceOf(PropertyMetadata::class, $this->getPropertyMetadata($metadata, 'name'));
        self::assertInstanceOf(PropertyMetadata::class, $this->getPropertyMetadata($metadata, 'email'));
    }

    public function testCreateWithBothObjects(): void
    {
        $metadata = $this->registry->getGeneratorMetadata(Fixtures\UserConstructorDTO::class, Fixtures\User::class);
        self::assertTrue($metadata->hasConstructor());
        self::assertTrue($metadata->isTargetCloneable());
        self::assertEquals(Fixtures\UserConstructorDTO::class, $metadata->mapperMetadata->source);
        self::assertEquals(Fixtures\User::class, $metadata->mapperMetadata->target);
        self::assertInstanceOf(PropertyMetadata::class, $this->getPropertyMetadata($metadata, 'id'));
        self::assertInstanceOf(PropertyMetadata::class, $this->getPropertyMetadata($metadata, 'name'));
        self::assertNull($this->getPropertyMetadata($metadata, 'email'));
        self::assertFalse($metadata->isTargetReadOnlyClass());
    }

    public function testHasNotConstructor(): void
    {
        $metadata = $this->registry->getGeneratorMetadata('array', Fixtures\UserDTO::class);

        self::assertFalse($metadata->hasConstructor());
    }

    /**
     * @requires PHP 8.2
     */
    public function testTargetIsReadOnlyClass(): void
    {
        $metadata = $this->registry->getGeneratorMetadata('array', Fixtures\AddressDTOReadonlyClass::class);

        self::assertEquals(Fixtures\AddressDTOReadonlyClass::class, $metadata->mapperMetadata->target);
        self::assertTrue($metadata->isTargetReadOnlyClass());
    }

    private function getPropertyMetadata(GeneratorMetadata $metadata, string $propertyName): ?PropertyMetadata
    {
        foreach ($metadata->propertiesMetadata as $propertyMetadata) {
            if ($propertyMetadata->source->name === $propertyName) {
                return $propertyMetadata;
            }
        }

        return null;
    }
}
