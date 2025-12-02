<?php

declare(strict_types=1);

namespace AutoMapper;

use AutoMapper\Exception\InvalidMappingException;
use AutoMapper\Generator\MapperGenerator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Loader\ClassLoaderInterface;
use AutoMapper\Loader\EvalLoader;
use AutoMapper\Loader\FileLoader;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Metadata\MetadataRegistry;
use AutoMapper\Provider\Doctrine\DoctrineProvider;
use AutoMapper\Provider\ProviderInterface;
use AutoMapper\Provider\ProviderRegistry;
use AutoMapper\Symfony\ExpressionLanguageProvider;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;
use AutoMapper\Transformer\TransformerFactoryInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Maps a source data structure (object or array) to a target one.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
class AutoMapper implements AutoMapperInterface, AutoMapperRegistryInterface
{
    /** @var array<GeneratedMapper<object, object>|GeneratedMapper<array<mixed>, object>|GeneratedMapper<object, array<mixed>>> */
    private array $mapperRegistry = [];

    public function __construct(
        private readonly ClassLoaderInterface $classLoader,
        private readonly PropertyTransformerRegistry $propertyTransformerRegistry,
        private readonly MetadataRegistry $metadataRegistry,
        private readonly ProviderRegistry $providerRegistry,
        private readonly ?ExpressionLanguageProvider $expressionLanguageProvider = null,
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
        $metadata = $this->metadataRegistry->get($source, $target);
        $className = $metadata->className;

        if (\array_key_exists($className, $this->mapperRegistry)) {
            /** @var GeneratedMapper<Source, Target>|GeneratedMapper<array<mixed>, Target>|GeneratedMapper<Source, array<mixed>> */
            return $this->mapperRegistry[$className];
        }

        if (!class_exists($className)) {
            $this->classLoader->loadClass($metadata);
        }

        /** @var GeneratedMapper<Source, Target>|GeneratedMapper<array<mixed>, Target>|GeneratedMapper<Source, array<mixed>> $mapper */
        $mapper = new $className(
            $this->propertyTransformerRegistry,
            $this->providerRegistry,
            $this->expressionLanguageProvider,
        );

        $this->mapperRegistry[$className] = $mapper;

        $mapper->registerMappers($this);

        return $mapper;
    }

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
            $context[MapperContext::DEEP_TARGET_TO_POPULATE] ??= true;
        } elseif (\is_array($target)) {
            $targetType = 'array';
            $context[MapperContext::TARGET_TO_POPULATE] = $target;
            $context[MapperContext::DEEP_TARGET_TO_POPULATE] ??= true;
        } elseif (\is_string($target)) {
            $targetType = $target;
        }

        if ('array' === $sourceType && 'array' === $targetType) {
            throw new InvalidMappingException('Cannot map this value, both source and target are array.');
        }

        return $this->getMapper($sourceType, $targetType)->map($source, $context);
    }

    public function mapCollection(iterable $collection, string $target, array $context = []): array
    {
        $output = [];
        foreach ($collection as $k => $item) {
            if (\is_array($item) && 'array' === $target) {
                throw new InvalidMappingException('Cannot map this value, both source and target are array.');
            }

            $output[$k] = $this->map($item, $target, $context);
        }

        return $output;
    }

    /**
     * @param TransformerFactoryInterface[]                  $transformerFactories
     * @param ProviderInterface[]                            $providers
     * @param iterable<string, PropertyTransformerInterface> $propertyTransformers
     *
     * @return self
     */
    public static function create(
        Configuration $configuration = new Configuration(),
        ?string $cacheDirectory = null,
        ?NameConverterInterface $nameConverter = null,
        array $transformerFactories = [],
        iterable $propertyTransformers = [],
        ?ExpressionLanguageProvider $expressionLanguageProvider = null,
        EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
        iterable $providers = [],
        ?ObjectManager $objectManager = null,
    ): AutoMapperInterface {
        if (class_exists(AttributeLoader::class)) {
            $loaderClass = new AttributeLoader();
        } else {
            $loaderClass = null;
        }

        $expressionLanguage = new ExpressionLanguage();

        if (null !== $expressionLanguageProvider) {
            $expressionLanguage->registerProvider($expressionLanguageProvider);
        }

        $classMetadataFactory = null;
        $classDiscriminatorFromClassMetadata = null;

        if (class_exists(ClassMetadataFactory::class) && $loaderClass !== null) {
            $classMetadataFactory = new ClassMetadataFactory($loaderClass);
            $classDiscriminatorFromClassMetadata = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);
        }

        $providers = iterator_to_array($providers);

        if (null !== $objectManager) {
            $providers[] = new DoctrineProvider($objectManager);
        }

        $customTransformerRegistry = new PropertyTransformerRegistry($propertyTransformers);
        $metadataRegistry = new MetadataRegistry($configuration);
        $providerRegistry = new ProviderRegistry($providers);
        $classDiscriminatorResolver = new ClassDiscriminatorResolver($classDiscriminatorFromClassMetadata);

        $metadataFactory = MetadataFactory::create(
            $configuration,
            $customTransformerRegistry,
            $metadataRegistry,
            $classDiscriminatorResolver,
            $classMetadataFactory,
            $nameConverter,
            $expressionLanguage,
            $eventDispatcher,
            $objectManager,
        );

        $mapperGenerator = new MapperGenerator(
            $classDiscriminatorResolver,
            $configuration,
            $expressionLanguage,
        );

        $lockFactory = new LockFactory(new FlockStore());

        if (null === $cacheDirectory) {
            $loader = new EvalLoader($mapperGenerator, $metadataFactory);
        } else {
            $loader = new FileLoader($mapperGenerator, $metadataFactory, $cacheDirectory, $lockFactory, $configuration->reloadStrategy);
        }

        return new self($loader, $customTransformerRegistry, $metadataRegistry, $providerRegistry, $expressionLanguageProvider);
    }
}
