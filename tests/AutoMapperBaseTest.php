<?php

namespace AutoMapper\Tests;

use AutoMapper\AutoMapper;
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

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
abstract class AutoMapperBaseTest extends TestCase
{
    /** @var AutoMapper */
    protected $autoMapper;

    /** @var ClassLoaderInterface */
    protected $loader;

    protected function setUp(): void
    {
        unset($this->autoMapper, $this->loader);
        $this->buildAutoMapper();
    }

    protected function buildAutoMapper(bool $allowReadOnlyTargetToPopulate = false): AutoMapper
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/cache/');
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $this->loader = new FileLoader(new Generator(
            (new ParserFactory())->create(ParserFactory::PREFER_PHP7),
            new ClassDiscriminatorFromClassMetadata($classMetadataFactory),
            $allowReadOnlyTargetToPopulate
        ), __DIR__ . '/cache');

        return $this->autoMapper = AutoMapper::create(true, $this->loader);
    }
}
