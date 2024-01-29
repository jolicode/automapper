<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\CustomTransformer\CustomTransformerInterface;
use AutoMapper\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Exception\NoMappingFoundException;
use AutoMapper\Extractor\ClassMethodToCallbackExtractor;
use AutoMapper\Extractor\CustomTransformerExtractor;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Extractor\MapToContextPropertyInfoExtractorDecorator;
use AutoMapper\Extractor\SourceTargetMappingExtractor;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Generator\TransformerResolver\ChainTransformerResolver;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Loader\EvalLoader;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataFactory;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataFactoryInterface;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataInterface;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataRegistry;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataRegistryInterface;
use AutoMapper\Transformer\ArrayTransformerFactory;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\DateTimeTransformerFactory;
use AutoMapper\Transformer\EnumTransformerFactory;
use AutoMapper\Transformer\MultipleTransformerFactory;
use AutoMapper\Transformer\NullableTransformerFactory;
use AutoMapper\Transformer\ObjectTransformerFactory;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\UniqueTypeTransformerFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * Maps a source data structure (object or array) to a target one.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class AutoMapper implements AutoMapperInterface, AutoMapperRegistryInterface, MapperGeneratorMetadataRegistryInterface
{
    public const VERSION = '8.2.0-DEV';
    public const VERSION_ID = 80200;
    public const MAJOR_VERSION = 8;
    public const MINOR_VERSION = 2;
    public const RELEASE_VERSION = 0;
    public const EXTRA_VERSION = 'DEV';

    /** @var GeneratedMapper[] */
    private array $mapperRegistry = [];

    public function __construct(
        private readonly ClassLoaderInterface $classLoader,
        private readonly ChainTransformerFactory $chainTransformerFactory,
        private readonly CustomTransformersRegistry $customTransformersRegistry,
        private readonly MapperGeneratorMetadataRegistryInterface $mapperGeneratorMetadataRegistry,
        private readonly MapperGenerator $mapperGenerator,
    ) {
    }

    public function getMapper(string $source, string $target): MapperInterface
    {
        $metadata = $this->mapperGeneratorMetadataRegistry->getMetadata($source, $target);

        if (null === $metadata) {
            throw new NoMappingFoundException('No mapping found for source ' . $source . ' and target ' . $target);
        }

        $className = $metadata->getMapperClassName();

        if (\array_key_exists($className, $this->mapperRegistry)) {
            return $this->mapperRegistry[$className];
        }

        if (!class_exists($className)) {
            $this->classLoader->loadClass($this->mapperGenerator, $metadata);
        }

        $this->mapperRegistry[$className] = new $className();
        $this->mapperRegistry[$className]->injectMappers($this);

        foreach ($metadata->getCallbacks() as $property => $callback) {
            $this->mapperRegistry[$className]->addCallback($property, $callback);
        }

        return $this->mapperRegistry[$className];
    }

    public function hasMapper(string $source, string $target): bool
    {
        return null !== $this->mapperGeneratorMetadataRegistry->getMetadata($source, $target);
    }

    public function map(array|object $source, string|array|object $target, array $context = []): null|array|object
    {
        $sourceType = $targetType = null;

        if (\is_object($source)) {
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

    public function bindTransformerFactory(TransformerFactoryInterface $transformerFactory): void
    {
        if (!$this->chainTransformerFactory->hasTransformerFactory($transformerFactory)) {
            $this->chainTransformerFactory->addTransformerFactory($transformerFactory);
        }
    }

    public function bindCustomTransformer(CustomTransformerInterface $customTransformer): void
    {
        $this->customTransformersRegistry->addCustomTransformer($customTransformer);
    }

    public function register(MapperGeneratorMetadataInterface $configuration): void
    {
        $this->mapperGeneratorMetadataRegistry->register($configuration);
    }

    public function getMetadata(string $source, string $target): ?MapperGeneratorMetadataInterface
    {
        return $this->mapperGeneratorMetadataRegistry->getMetadata($source, $target);
    }

    public static function create(
        bool $mapPrivateProperties = false,
        ClassLoaderInterface $loader = null,
        AdvancedNameConverterInterface $nameConverter = null,
        string $classPrefix = 'Mapper_',
        bool $attributeChecking = true,
        bool $autoRegister = true,
        string $dateTimeFormat = \DateTimeInterface::RFC3339,
        bool $allowReadOnlyTargetToPopulate = false
    ): self {
        if (class_exists(AttributeLoader::class)) {
            $loaderClass = new AttributeLoader();
        } elseif (class_exists(AnnotationReader::class)) {
            $loaderClass = new AnnotationLoader(new AnnotationReader());
        } else {
            $loaderClass = new AnnotationLoader();
        }
        $classMetadataFactory = new ClassMetadataFactory($loaderClass);

        $flags = ReflectionExtractor::ALLOW_PUBLIC | ReflectionExtractor::ALLOW_PROTECTED | ReflectionExtractor::ALLOW_PRIVATE;

        $reflectionExtractor = new ReflectionExtractor(accessFlags: $flags);

        $phpStanExtractor = new PhpStanExtractor();
        $propertyInfoExtractor = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpStanExtractor, $reflectionExtractor],
            [$reflectionExtractor],
            [new MapToContextPropertyInfoExtractorDecorator($reflectionExtractor)]
        );

        $customTransformerRegistry = new CustomTransformersRegistry();

        $transformerFactory = new ChainTransformerFactory();
        $sourceTargetMappingExtractor = new SourceTargetMappingExtractor(
            $propertyInfoExtractor,
            new MapToContextPropertyInfoExtractorDecorator($reflectionExtractor),
            $reflectionExtractor,
            $transformerFactory,
            $customTransformerRegistry,
            $classMetadataFactory
        );

        $fromTargetMappingExtractor = new FromTargetMappingExtractor(
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $transformerFactory,
            $customTransformerRegistry,
            $classMetadataFactory,
            $nameConverter
        );

        $fromSourceMappingExtractor = new FromSourceMappingExtractor(
            $propertyInfoExtractor,
            new MapToContextPropertyInfoExtractorDecorator($reflectionExtractor),
            $reflectionExtractor,
            $transformerFactory,
            $customTransformerRegistry,
            $classMetadataFactory,
            $nameConverter
        );

        $mapperConfigurationFactory = null;
        if ($autoRegister) {
            $mapperConfigurationFactory = new MapperGeneratorMetadataFactory(
                $sourceTargetMappingExtractor,
                $fromSourceMappingExtractor,
                $fromTargetMappingExtractor,
                $classPrefix,
                $attributeChecking,
                $dateTimeFormat,
                $mapPrivateProperties
            );
        }

        $mapperGeneratorMetadataRegistry = new MapperGeneratorMetadataRegistry($mapperConfigurationFactory);

        $autoMapper = new self(
            $loader ?? new EvalLoader(),
            $transformerFactory,
            $customTransformerRegistry,
            $mapperGeneratorMetadataRegistry,
            new MapperGenerator(
                new CustomTransformerExtractor(new ClassMethodToCallbackExtractor()),
                new ClassDiscriminatorResolver(new ClassDiscriminatorFromClassMetadata($classMetadataFactory)),
                $mapperGeneratorMetadataRegistry,
                $allowReadOnlyTargetToPopulate,
            )
        );

        $transformerFactory->addTransformerFactory(new MultipleTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new NullableTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new UniqueTypeTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new DateTimeTransformerFactory());
        $transformerFactory->addTransformerFactory(new BuiltinTransformerFactory());
        $transformerFactory->addTransformerFactory(new ArrayTransformerFactory($transformerFactory));
        $transformerFactory->addTransformerFactory(new ObjectTransformerFactory($autoMapper));
        $transformerFactory->addTransformerFactory(new EnumTransformerFactory());

        if (class_exists(AbstractUid::class)) {
            $transformerFactory->addTransformerFactory(new SymfonyUidTransformerFactory());
        }

        return $autoMapper;
    }
}
