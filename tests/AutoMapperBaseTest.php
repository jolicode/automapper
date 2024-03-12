<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\AutoMapper;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Loader\FileLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
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

    protected function buildAutoMapper(bool $allowReadOnlyTargetToPopulate = false, bool $mapPrivatePropertiesAndMethod = false, string $classPrefix = 'Mapper_', array $transformerFactories = []): AutoMapper
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/cache/');

        if (class_exists(AttributeLoader::class)) {
            $loaderClass = new AttributeLoader();
        } else {
            $loaderClass = new AnnotationLoader(new AnnotationReader());
        }
        $classMetadataFactory = new ClassMetadataFactory($loaderClass);

        $this->loader = new FileLoader(new MapperGenerator(
            new ClassDiscriminatorResolver(new ClassDiscriminatorFromClassMetadata($classMetadataFactory)),
            $allowReadOnlyTargetToPopulate
        ), __DIR__ . '/cache');

        return $this->autoMapper = AutoMapper::create($mapPrivatePropertiesAndMethod, $this->loader, classPrefix: $classPrefix, transformerFactories: $transformerFactories);
    }
}
