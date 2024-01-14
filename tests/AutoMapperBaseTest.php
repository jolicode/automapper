<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\AutoMapper;
use AutoMapper\Extractor\ClassMethodToCallbackExtractor;
use AutoMapper\Extractor\CustomTransformerExtractor;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Loader\FileLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
abstract class AutoMapperBaseTest extends TestCase
{
    protected AutoMapper $autoMapper;
    protected ClassLoaderInterface $loader;

    protected function setUp(): void
    {
        unset($this->autoMapper, $this->loader);
        $this->buildAutoMapper();
    }

    protected function buildAutoMapper(bool $allowReadOnlyTargetToPopulate = false, bool $mapPrivatePropertiesAndMethod = false, string $classPrefix = 'Mapper_'): AutoMapper
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/cache/');
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());

        $this->loader = new FileLoader(new MapperGenerator(
            new CustomTransformerExtractor(new ClassMethodToCallbackExtractor()),
            new ClassDiscriminatorResolver(new ClassDiscriminatorFromClassMetadata($classMetadataFactory)),
            $allowReadOnlyTargetToPopulate
        ), __DIR__ . '/cache');

        return $this->autoMapper = AutoMapper::create($mapPrivatePropertiesAndMethod, $this->loader, classPrefix: $classPrefix);
    }
}
