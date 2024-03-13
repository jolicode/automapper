<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\Exception\NoMappingFoundException;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Loader\EvalLoader;
use AutoMapper\Loader\FileLoader;
use AutoMapper\Metadata\MetadataRegistry;
use AutoMapper\Transformer\CustomTransformer\CustomTransformerInterface;
use AutoMapper\Transformer\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Transformer\TransformerFactoryInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

/**
 * Maps a source data structure (object or array) to a target one.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class AutoMapper implements AutoMapperInterface, AutoMapperRegistryInterface
{
    public const VERSION = '8.3.0-DEV';
    public const VERSION_ID = 80300;
    public const MAJOR_VERSION = 8;
    public const MINOR_VERSION = 3;
    public const RELEASE_VERSION = 0;
    public const EXTRA_VERSION = 'DEV';

    /** @var GeneratedMapper[] */
    private array $mapperRegistry = [];

    public function __construct(
        private readonly ClassLoaderInterface $classLoader,
        public readonly CustomTransformersRegistry $customTransformersRegistry,
        public readonly MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * @param class-string<object>|'array' $source
     * @param class-string<object>|'array' $target
     */
    public function getMapper(string $source, string $target): MapperInterface
    {
        $metadata = $this->metadataRegistry->getMapperMetadata($source, $target);
        $className = $metadata->className;

        if (\array_key_exists($className, $this->mapperRegistry)) {
            return $this->mapperRegistry[$className];
        }

        if (!class_exists($className)) {
            $this->classLoader->loadClass($metadata);
        }

        /** @var GeneratedMapper $mapper */
        $mapper = new $className();
        $this->mapperRegistry[$className] = $mapper;

        $mapper->injectMappers($this);
        $mapper->setCustomTransformers($this->customTransformersRegistry->getCustomTransformers());

        return $this->mapperRegistry[$className];
    }

    /**
     * @param class-string<object>|array|object $target
     */
    public function map(array|object $source, string|array|object $target, array $context = []): array|object|null
    {
        $sourceType = $targetType = null;

        if (\is_object($source)) {
            /** @var class-string<object> $sourceType */
            $sourceType = $source::class;
        } elseif (\is_array($source)) {
            $sourceType = 'array';
        }

        if (\is_object($target)) {
            $targetType = $target::class;
            $context[MapperContext::TARGET_TO_POPULATE] = $target;
        } elseif (\is_array($target)) {
            $targetType = 'array';
            $context[MapperContext::TARGET_TO_POPULATE] = $target;
        } elseif (\is_string($target)) {
            $targetType = $target;
        }

        if ('array' === $sourceType && 'array' === $targetType) {
            throw new NoMappingFoundException('Cannot map this value, both source and target are array.');
        }

        return $this->getMapper($sourceType, $targetType)->map($source, $context);
    }

    public function bindCustomTransformer(CustomTransformerInterface $customTransformer, ?string $id = null): void
    {
        $this->customTransformersRegistry->addCustomTransformer($customTransformer, $id);
    }

    /**
     * @param TransformerFactoryInterface[] $transformerFactories
     */
    public static function create(
        Configuration $configuration = new Configuration(),
        string $cacheDirectory = null,
        AdvancedNameConverterInterface $nameConverter = null,
        array $transformerFactories = [],
    ): self {
        if (class_exists(AttributeLoader::class)) {
            $loaderClass = new AttributeLoader();
        } elseif (class_exists(AnnotationReader::class)) {
            $loaderClass = new AnnotationLoader(new AnnotationReader());
        } else {
            $loaderClass = new AnnotationLoader();
        }
        $classMetadataFactory = new ClassMetadataFactory($loaderClass);
        $customTransformerRegistry = new CustomTransformersRegistry();
        $metadataRegistry = MetadataRegistry::create($configuration, $customTransformerRegistry, $classMetadataFactory, $nameConverter, $transformerFactories);

        $mapperGenerator = new MapperGenerator(
            new ClassDiscriminatorResolver(new ClassDiscriminatorFromClassMetadata($classMetadataFactory)),
            $configuration->allowReadOnlyTargetToPopulate,
            !$configuration->autoRegister,
        );

        if (null === $cacheDirectory) {
            $loader = new EvalLoader($mapperGenerator, $metadataRegistry);
        } else {
            $loader = new FileLoader($mapperGenerator, $metadataRegistry, $cacheDirectory);
        }

        return new self($loader, $customTransformerRegistry, $metadataRegistry);
    }
}
