<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Extractor;

use AutoMapper\Configuration;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Tests\AutoMapperBaseTest;
use AutoMapper\Tests\Fixtures;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

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

        $this->fromSourceMappingExtractor = new FromSourceMappingExtractor(
            new Configuration(),
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
        );
    }

    public function testWithTargetAsArray(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);
        $mapperMetadata = new MapperMetadata(source: Fixtures\User::class, target: 'array');
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getProperties($mapperMetadata->source);

        self::assertCount(\count($userReflection->getProperties()), $sourcePropertiesMapping);
        foreach ($sourcePropertiesMapping as $propertyMapping) {
            self::assertTrue($userReflection->hasProperty($propertyMapping));
        }
    }

    public function testWithTargetAsStdClass(): void
    {
        $userReflection = new \ReflectionClass(Fixtures\User::class);
        $mapperMetadata = new MapperMetadata(source: Fixtures\User::class, target: 'stdClass');
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getProperties($mapperMetadata->source);

        self::assertCount(\count($userReflection->getProperties()), $sourcePropertiesMapping);

        foreach ($sourcePropertiesMapping as $propertyMapping) {
            self::assertTrue($userReflection->hasProperty($propertyMapping));
        }
    }

    public function testWithSourceAsEmpty(): void
    {
        $mapperMetadata = new MapperMetadata(source: Fixtures\Empty_::class, target: 'array');
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getProperties($mapperMetadata->source);

        self::assertCount(0, $sourcePropertiesMapping);
    }

    public function testWithSourceAsPrivate(): void
    {
        $privateReflection = new \ReflectionClass(Fixtures\Private_::class);
        $mapperMetadata = new MapperMetadata(source: Fixtures\Private_::class, target: 'array');
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getProperties($mapperMetadata->source);
        self::assertCount(\count($privateReflection->getProperties()), $sourcePropertiesMapping);

        $this->fromSourceMappingExtractorBootstrap(false);
        $mapperMetadata = new MapperMetadata(source: Fixtures\Private_::class, target: 'array');
        $sourcePropertiesMapping = $this->fromSourceMappingExtractor->getProperties($mapperMetadata->source);
        self::assertCount(0, $sourcePropertiesMapping);
    }

    public function testWithSourceAsArray(): void
    {
        $mapperMetadata = new MapperMetadata(source: 'array', target: Fixtures\User::class);
        self::assertCount(0, $this->fromSourceMappingExtractor->getProperties($mapperMetadata->source));
    }

    public function testWithSourceAsStdClass(): void
    {
        $mapperMetadata = new MapperMetadata(source: 'stdClass', target: Fixtures\User::class);
        $this->fromSourceMappingExtractor->getProperties($mapperMetadata->source);
        self::assertCount(0, $this->fromSourceMappingExtractor->getProperties($mapperMetadata->source));
    }
}
