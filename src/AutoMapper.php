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
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;
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
    public const VERSION = '9.0.0-DEV';
    public const VERSION_ID = 90000;
    public const MAJOR_VERSION = 9;
    public const MINOR_VERSION = 0;
    public const RELEASE_VERSION = 0;
    public const EXTRA_VERSION = 'DEV';

    /** @var array<GeneratedMapper<object, object>|GeneratedMapper<array<mixed>, object>|GeneratedMapper<object, array<mixed>>> */
    private array $mapperRegistry = [];

    public function __construct(
        private readonly ClassLoaderInterface $classLoader,
        private readonly PropertyTransformerRegistry $propertyTransformerRegistry,
        private readonly MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * @template Source of object
     * @template Target of object
     *
     * @param class-string<Source>|'array' $source
     * @param class-string<Target>|'array' $target
     *
     * @return ($source is class-string ? ($target is 'array' ? MapperInterface<Source, array<mixed>> : MapperInterface<Source, Target>) : MapperInterface<array<mixed>, Target>)
     */
    public function getMapper(string $source, string $target): MapperInterface
    {
        $metadata = $this->metadataRegistry->getMapperMetadata($source, $target);
        $className = $metadata->className;

        if (\array_key_exists($className, $this->mapperRegistry)) {
            /** @var GeneratedMapper<Source, Target>|GeneratedMapper<array<mixed>, Target>|GeneratedMapper<Source, array<mixed>> */
            return $this->mapperRegistry[$className];
        }

        if (!class_exists($className)) {
            $this->classLoader->loadClass($metadata);
        }

        /** @var GeneratedMapper<Source, Target>|GeneratedMapper<array<mixed>, Target>|GeneratedMapper<Source, array<mixed>> $mapper */
        $mapper = new $className();
        $this->mapperRegistry[$className] = $mapper;

        $mapper->injectMappers($this);
        $mapper->setPropertyTransformers($this->propertyTransformerRegistry->getPropertyTransformers());

        /** @var GeneratedMapper<Source, Target>|GeneratedMapper<array<mixed>, Target>|GeneratedMapper<Source, array<mixed>> */
        return $this->mapperRegistry[$className];
    }

    /**
     * @template Source of object
     * @template Target of object
     *
     * @param Source|array<mixed>                              $source
     * @param class-string<Target>|'array'|array<mixed>|Target $target
     *
     * @return ($target is class-string|Target ? Target|null : array<mixed>|null)
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

    /**
     * @param TransformerFactoryInterface[]                  $transformerFactories
     * @param iterable<string, PropertyTransformerInterface> $propertyTransformers
     */
    public static function create(
        Configuration $configuration = new Configuration(),
        string $cacheDirectory = null,
        AdvancedNameConverterInterface $nameConverter = null,
        array $transformerFactories = [],
        iterable $propertyTransformers = [],
    ): self {
        if (class_exists(AttributeLoader::class)) {
            $loaderClass = new AttributeLoader();
        } elseif (class_exists(AnnotationReader::class) && class_exists(AnnotationLoader::class)) {
            $loaderClass = new AnnotationLoader(new AnnotationReader());
        } else {
            $loaderClass = null;
        }

        $classMetadataFactory = null;
        $classDiscriminatorFromClassMetadata = null;

        if (class_exists(ClassMetadataFactory::class) && $loaderClass !== null) {
            $classMetadataFactory = new ClassMetadataFactory($loaderClass);
            $classDiscriminatorFromClassMetadata = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);
        }

        $customTransformerRegistry = new PropertyTransformerRegistry($propertyTransformers);
        $metadataRegistry = MetadataRegistry::create($configuration, $customTransformerRegistry, $transformerFactories, $classMetadataFactory, $nameConverter);

        $mapperGenerator = new MapperGenerator(
            new ClassDiscriminatorResolver($classDiscriminatorFromClassMetadata),
            $configuration,
        );

        if (null === $cacheDirectory) {
            $loader = new EvalLoader($mapperGenerator, $metadataRegistry);
        } else {
            $loader = new FileLoader($mapperGenerator, $metadataRegistry, $cacheDirectory);
        }

        return new self($loader, $customTransformerRegistry, $metadataRegistry);
    }
}
