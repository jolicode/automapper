<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Extractor;

use AutoMapper\Configuration;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Tests\AutoMapperTestCase;
use AutoMapper\Tests\Fixtures;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
class FromTargetMappingExtractorTest extends AutoMapperTestCase
{
    protected FromTargetMappingExtractor $fromTargetMappingExtractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fromTargetMappingExtractorBootstrap();
    }

    private function fromTargetMappingExtractorBootstrap(bool $private = true): void
    {
        $flags = ReflectionExtractor::ALLOW_PUBLIC;

        if ($private) {
            $flags |= ReflectionExtractor::ALLOW_PROTECTED | ReflectionExtractor::ALLOW_PRIVATE;
        }

        $reflectionExtractor = new ReflectionExtractor(null, null, null, true, $flags);

        $phpStanExtractor = new PhpStanExtractor();
        $propertyInfoExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpStanExtractor, $reflectionExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor]
        );

        $this->fromTargetMappingExtractor = new FromTargetMappingExtractor(
            new Configuration(),
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
        );
    }

    public function testWithSourceAsArray(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);
        $mapperMetadata = new MapperMetadata('array', target: Fixtures\User::class, registered: true);
        $targetPropertiesMapping = $this->fromTargetMappingExtractor->getProperties($mapperMetadata->target);

        self::assertCount(\count($userReflection->getProperties()), $targetPropertiesMapping);
        foreach ($targetPropertiesMapping as $propertyMapping) {
            self::assertTrue($userReflection->hasProperty($propertyMapping));
        }
    }

    public function testWithSourceAsStdClass(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);
        $mapperMetadata = new MapperMetadata('stdClass', target: Fixtures\User::class, registered: true);
        $targetPropertiesMapping = $this->fromTargetMappingExtractor->getProperties($mapperMetadata->target);

        self::assertCount(\count($userReflection->getProperties()), $targetPropertiesMapping);
        foreach ($targetPropertiesMapping as $propertyMapping) {
            self::assertTrue($userReflection->hasProperty($propertyMapping));
        }
    }

    public function testWithTargetAsEmpty(): void
    {
        $mapperMetadata = new MapperMetadata('array', target: Fixtures\Empty_::class, registered: true);
        $targetPropertiesMapping = $this->fromTargetMappingExtractor->getProperties($mapperMetadata->target);

        self::assertCount(0, $targetPropertiesMapping);
    }

    public function testWithTargetAsPrivate(): void
    {
        $privateReflection = new \ReflectionClass(Fixtures\Private_::class);
        $mapperMetadata = new MapperMetadata(source: 'array', target: Fixtures\Private_::class, registered: true);

        $targetPropertiesMapping = $this->fromTargetMappingExtractor->getProperties($mapperMetadata->target);
        self::assertCount(\count($privateReflection->getProperties()), $targetPropertiesMapping);

        $this->fromTargetMappingExtractorBootstrap(false);
        $mapperMetadata = new MapperMetadata(source: 'array', target: Fixtures\Private_::class, registered: true);

        $targetPropertiesMapping = $this->fromTargetMappingExtractor->getProperties($mapperMetadata->target);
        self::assertCount(0, $targetPropertiesMapping);
    }

    public function testWithTargetAsArray(): void
    {
        $mapperMetadata = new MapperMetadata(source: Fixtures\User::class, target: 'array', registered: true);
        $targetPropertiesMapping = $this->fromTargetMappingExtractor->getProperties($mapperMetadata->target);
        self::assertCount(0, $targetPropertiesMapping);
    }

    public function testWithTargetAsStdClass(): void
    {
        $mapperMetadata = new MapperMetadata(source: Fixtures\User::class, target: 'stdClass', registered: true);
        $targetPropertiesMapping = $this->fromTargetMappingExtractor->getProperties($mapperMetadata->target);
        self::assertCount(0, $targetPropertiesMapping);
    }
}
