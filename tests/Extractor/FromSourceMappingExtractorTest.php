<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Extractor;

use AutoMapper\Exception\InvalidMappingException;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\MapperMetadata;
use AutoMapper\Tests\AutoMapperBaseTest;
use AutoMapper\Tests\Fixtures;
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
class FromSourceMappingExtractorTest extends AutoMapperBaseTest
{
    protected FromSourceMappingExtractor $fromSourceMappingExtractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fromSourceMappingExtractorBootstrap();
    }

    private function fromSourceMappingExtractorBootstrap(bool $private = true): void
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $flags = ReflectionExtractor::ALLOW_PUBLIC;

        if ($private) {
            $flags |= ReflectionExtractor::ALLOW_PROTECTED | ReflectionExtractor::ALLOW_PRIVATE;
        }

        $reflectionExtractor = new ReflectionExtractor(null, null, null, true, $flags);
        $transformerFactory = new ChainTransformerFactory();

        $phpDocExtractor = new PhpDocExtractor();
        $propertyInfoExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpDocExtractor, $reflectionExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor]
        );

        $this->fromSourceMappingExtractor = new FromSourceMappingExtractor(
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $transformerFactory,
            new CustomTransformersRegistry(),
            $classMetadataFactory
        );

        $transformerFactory->addTransformerFactory(new MultipleTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new NullableTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new UniqueTypeTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new DateTimeTransformerFactory());
        $transformerFactory->addTransformerFactory(new BuiltinTransformerFactory());
        $transformerFactory->addTransformerFactory(new ArrayTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new ObjectTransformerFactory($this->autoMapper));
    }

    public function testWithTargetAsArray(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);
        $mapperMetadata = new MapperMetadata($this->autoMapper, $this->fromSourceMappingExtractor, source: Fixtures\User::class, target: 'array', isTargetReadOnlyClass: false, mapPrivateProperties: true);
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getPropertiesMapping($mapperMetadata);

        self::assertCount(\count($userReflection->getProperties()), $sourcePropertiesMapping);
        /** @var PropertyMapping $propertyMapping */
        foreach ($sourcePropertiesMapping as $propertyMapping) {
            self::assertTrue($userReflection->hasProperty($propertyMapping->property));
        }
    }

    public function testWithTargetAsStdClass(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);
        $mapperMetadata = new MapperMetadata($this->autoMapper, $this->fromSourceMappingExtractor, source: Fixtures\User::class, target: 'stdClass', isTargetReadOnlyClass: false, mapPrivateProperties: true);
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getPropertiesMapping($mapperMetadata);

        self::assertCount(\count($userReflection->getProperties()), $sourcePropertiesMapping);
        /** @var PropertyMapping $propertyMapping */
        foreach ($sourcePropertiesMapping as $propertyMapping) {
            self::assertTrue($userReflection->hasProperty($propertyMapping->property));
        }
    }

    public function testWithSourceAsEmpty(): void
    {
        $mapperMetadata = new MapperMetadata($this->autoMapper, $this->fromSourceMappingExtractor, source: Fixtures\Empty_::class, target: 'array', isTargetReadOnlyClass: false, mapPrivateProperties: true);
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getPropertiesMapping($mapperMetadata);

        self::assertCount(0, $sourcePropertiesMapping);
    }

    public function testWithSourceAsPrivate(): void
    {
        $privateReflection = new \ReflectionClass(Fixtures\Private_::class);
        $mapperMetadata = new MapperMetadata($this->autoMapper, $this->fromSourceMappingExtractor, source: Fixtures\Private_::class, target: 'array', isTargetReadOnlyClass: false, mapPrivateProperties: true);
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getPropertiesMapping($mapperMetadata);
        self::assertCount(\count($privateReflection->getProperties()), $sourcePropertiesMapping);

        $this->fromSourceMappingExtractorBootstrap(false);
        $mapperMetadata = new MapperMetadata($this->autoMapper, $this->fromSourceMappingExtractor, source: Fixtures\Private_::class, target: 'array', isTargetReadOnlyClass: false, mapPrivateProperties: true);
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getPropertiesMapping($mapperMetadata);
        self::assertCount(0, $sourcePropertiesMapping);
    }

    public function testWithSourceAsArray(): void
    {
        self::expectException(InvalidMappingException::class);
        self::expectExceptionMessage('Only array or stdClass are accepted as a target');

        $mapperMetadata = new MapperMetadata($this->autoMapper, $this->fromSourceMappingExtractor, source: 'array', target: Fixtures\User::class, isTargetReadOnlyClass: false, mapPrivateProperties: true);
        $this->fromSourceMappingExtractor->getPropertiesMapping($mapperMetadata);
    }

    public function testWithSourceAsStdClass(): void
    {
        self::expectException(InvalidMappingException::class);
        self::expectExceptionMessage('Only array or stdClass are accepted as a target');

        $mapperMetadata = new MapperMetadata($this->autoMapper, $this->fromSourceMappingExtractor, source: 'stdClass', target: Fixtures\User::class, isTargetReadOnlyClass: false, mapPrivateProperties: true);
        $this->fromSourceMappingExtractor->getPropertiesMapping($mapperMetadata);
    }
}
