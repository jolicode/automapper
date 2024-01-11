<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\AutoMapper;
use AutoMapper\Extractor\ClassMethodToCallbackExtractor;
use AutoMapper\Generator\Generator;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Loader\FileLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use PhpParser\ParserFactory;
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

    protected function buildAutoMapper(bool $allowReadOnlyTargetToPopulate = false, bool $mapPrivatePropertiesAndMethod = false, string $classPrefix = 'Mapper_'): AutoMapper
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/cache/');
        if (class_exists(AttributeLoader::class)) {
            $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        } else {
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        }

        $this->loader = new FileLoader(new Generator(
            new ClassMethodToCallbackExtractor(),
            (new ParserFactory())->createForHostVersion(),
            new ClassDiscriminatorFromClassMetadata($classMetadataFactory),
            $allowReadOnlyTargetToPopulate
        ), __DIR__ . '/cache');

        return $this->autoMapper = AutoMapper::create($mapPrivatePropertiesAndMethod, $this->loader, classPrefix: $classPrefix);
    }
}
