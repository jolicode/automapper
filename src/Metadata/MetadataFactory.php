<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Configuration;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata as SourcePropertyMetadataEvent;
use AutoMapper\Event\TargetPropertyMetadata as TargetPropertyMetadataEvent;
use AutoMapper\EventListener\MapFromListener;
use AutoMapper\EventListener\MapProviderListener;
use AutoMapper\EventListener\MapToContextListener;
use AutoMapper\EventListener\MapToListener;
use AutoMapper\EventListener\Symfony\AdvancedNameConverterListener;
use AutoMapper\EventListener\Symfony\SerializerGroupListener;
use AutoMapper\EventListener\Symfony\SerializerIgnoreListener;
use AutoMapper\EventListener\Symfony\SerializerMaxDepthListener;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Extractor\ReadWriteTypeExtractor;
use AutoMapper\Extractor\SourceTargetMappingExtractor;
use AutoMapper\Transformer\ArrayTransformerFactory;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\CopyTransformerFactory;
use AutoMapper\Transformer\DateTimeTransformerFactory;
use AutoMapper\Transformer\DependentTransformerInterface;
use AutoMapper\Transformer\EnumTransformerFactory;
use AutoMapper\Transformer\MultipleTransformerFactory;
use AutoMapper\Transformer\NullableTransformerFactory;
use AutoMapper\Transformer\ObjectTransformerFactory;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerFactory;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\UniqueTypeTransformerFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * @internal
 */
final class MetadataFactory
{
    /** @var array<string, array<string, GeneratorMetadata>> */
    private array $generatorMetadata = [];

    public function __construct(
        private readonly Configuration $configuration,
        private readonly SourceTargetMappingExtractor $sourceTargetPropertiesMappingExtractor,
        private readonly FromSourceMappingExtractor $fromSourcePropertiesMappingExtractor,
        private readonly FromTargetMappingExtractor $fromTargetPropertiesMappingExtractor,
        private readonly TransformerFactoryInterface $transformerFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        public readonly MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * @param class-string<object>|'array' $source
     * @param class-string<object>|'array' $target
     *
     * @internal
     */
    public function getGeneratorMetadata(string $source, string $target): GeneratorMetadata
    {
        if (!isset($this->generatorMetadata[$source][$target])) {
            $metadata = $this->createGeneratorMetadata($this->metadataRegistry->get($source, $target));
            $this->generatorMetadata[$source][$target] = $metadata;

            // Add dependencies to the mapper
            foreach ($metadata->propertiesMetadata as $propertyMapping) {
                if ($propertyMapping->transformer instanceof DependentTransformerInterface) {
                    foreach ($propertyMapping->transformer->getDependencies() as $mapperDependency) {
                        $dependencyMetadata = $this->getGeneratorMetadata($mapperDependency->source, $mapperDependency->target);

                        $metadata->addDependency(new Dependency($mapperDependency, $dependencyMetadata));
                    }
                }
            }
        }

        return $this->generatorMetadata[$source][$target];
    }

    public function resolveAllMetadata(MetadataRegistry $metadataRegistry): void
    {
        $localGeneratorRegistry = [];
        $remainingMetadata = iterator_to_array($metadataRegistry);

        while (!empty($remainingMetadata)) {
            $mapperMetadata = array_shift($remainingMetadata);

            if (isset($localGeneratorRegistry[$mapperMetadata->source][$mapperMetadata->target])) {
                continue;
            }

            $generatorMetadata = $this->createGeneratorMetadata($mapperMetadata);
            $localGeneratorRegistry[$mapperMetadata->source][$mapperMetadata->target] = $generatorMetadata;

            foreach ($generatorMetadata->propertiesMetadata as $propertyMetadata) {
                if ($propertyMetadata->transformer instanceof DependentTransformerInterface) {
                    foreach ($propertyMetadata->transformer->getDependencies() as $mapperDependency) {
                        $remainingMetadata[] = $metadataRegistry->get($mapperDependency->source, $mapperDependency->target);
                    }
                }
            }
        }
    }

    private function createGeneratorMetadata(MapperMetadata $mapperMetadata): GeneratorMetadata
    {
        $extractor = $this->sourceTargetPropertiesMappingExtractor;

        if ('array' === $mapperMetadata->source || 'stdClass' === $mapperMetadata->source) {
            $extractor = $this->fromTargetPropertiesMappingExtractor;
        }

        if ('array' === $mapperMetadata->target || 'stdClass' === $mapperMetadata->target) {
            $extractor = $this->fromSourcePropertiesMappingExtractor;
        }

        $propertyEvents = [];

        $mapperEvent = new GenerateMapperEvent($mapperMetadata);
        $this->eventDispatcher->dispatch($mapperEvent);

        // First get properties from the source
        foreach ($extractor->getProperties($mapperMetadata->source) as $property) {
            $propertyEvent = new PropertyMetadataEvent($mapperMetadata, new SourcePropertyMetadataEvent($property), new TargetPropertyMetadataEvent($property));

            $this->eventDispatcher->dispatch($propertyEvent);

            $propertyEvents[$propertyEvent->target->name] = $propertyEvent;
        }

        foreach ($extractor->getProperties($mapperMetadata->target) as $property) {
            if (isset($propertyEvents[$property])) {
                continue;
            }

            $propertyEvent = new PropertyMetadataEvent($mapperMetadata, new SourcePropertyMetadataEvent($property), new TargetPropertyMetadataEvent($property));

            $this->eventDispatcher->dispatch($propertyEvent);

            $propertyEvents[$propertyEvent->target->name] = $propertyEvent;
        }

        foreach ($mapperEvent->properties as $propertyEvent) {
            $this->eventDispatcher->dispatch($propertyEvent);

            $propertyEvents[$propertyEvent->target->name] = $propertyEvent;
        }

        // Sort transformations by property name, to ensure consistent order, and easier debugging
        ksort($propertyEvents, SORT_NATURAL);

        $propertiesMapping = [];

        foreach ($propertyEvents as $propertyMappedEvent) {
            // Create the source property metadata
            if ($propertyMappedEvent->source->accessor === null) {
                $propertyMappedEvent->source->accessor = $extractor->getReadAccessor($mapperMetadata->source, $propertyMappedEvent->source->name);
            }

            if ($propertyMappedEvent->source->checkExists === null) {
                $propertyMappedEvent->source->checkExists = $extractor->getCheckExists($mapperMetadata->source, $propertyMappedEvent->source->name);
            }

            if ($propertyMappedEvent->source->extractGroupsIfNull && $propertyMappedEvent->source->groups === null) {
                $propertyMappedEvent->source->groups = $extractor->getGroups($mapperMetadata->source, $propertyMappedEvent->source->name);
            }

            if ($propertyMappedEvent->source->dateTimeFormat === null) {
                $propertyMappedEvent->source->dateTimeFormat = $extractor->getDateTimeFormat($mapperMetadata->source, $propertyMappedEvent->source->name);
            }

            // Create the target property metadata
            if ($propertyMappedEvent->target->readAccessor === null) {
                $propertyMappedEvent->target->readAccessor = $extractor->getReadAccessor($mapperMetadata->target, $propertyMappedEvent->target->name);
            }

            if ($propertyMappedEvent->target->writeMutator === null) {
                $propertyMappedEvent->target->writeMutator = $extractor->getWriteMutator($mapperMetadata->source, $mapperMetadata->target, $propertyMappedEvent->target->name, [
                    'enable_constructor_extraction' => false,
                ]);
            }

            if ($propertyMappedEvent->target->writeMutatorConstructor === null) {
                $propertyMappedEvent->target->writeMutatorConstructor = $extractor->getWriteMutator($mapperMetadata->source, $mapperMetadata->target, $propertyMappedEvent->target->name, [
                    'enable_constructor_extraction' => true,
                ]);
            }

            if ($propertyMappedEvent->target->extractGroupsIfNull && $propertyMappedEvent->target->groups === null) {
                $propertyMappedEvent->target->groups = $extractor->getGroups($mapperMetadata->target, $propertyMappedEvent->target->name);
            }

            if ($propertyMappedEvent->target->dateTimeFormat === null) {
                $propertyMappedEvent->target->dateTimeFormat = $extractor->getDateTimeFormat($mapperMetadata->target, $propertyMappedEvent->target->name);
            }

            $sourcePropertyMetadata = SourcePropertyMetadata::fromEvent($propertyMappedEvent->source);
            $targetPropertyMetadata = TargetPropertyMetadata::fromEvent($propertyMappedEvent->target);

            if (null === $propertyMappedEvent->types) {
                $propertyMappedEvent->types = $extractor->getTypes($mapperMetadata->source, $sourcePropertyMetadata, $mapperMetadata->target, $targetPropertyMetadata);
            }

            if (null === $propertyMappedEvent->transformer) {
                $transformer = $this->transformerFactory->getTransformer($propertyMappedEvent->types, $sourcePropertyMetadata, $targetPropertyMetadata, $mapperMetadata);

                if (null === $transformer) {
                    continue;
                }

                $propertyMappedEvent->transformer = $transformer;
            }

            $propertiesMapping[] = new PropertyMetadata(
                $sourcePropertyMetadata,
                $targetPropertyMetadata,
                $propertyMappedEvent->types,
                $propertyMappedEvent->transformer,
                $propertyMappedEvent->ignored ?? false,
                $propertyMappedEvent->maxDepth,
                $propertyMappedEvent->if,
                $propertyMappedEvent->groups,
                $propertyMappedEvent->disableGroupsCheck,
            );
        }

        return new GeneratorMetadata(
            $mapperMetadata,
            $propertiesMapping,
            $this->configuration->attributeChecking,
            $this->configuration->allowConstructor,
            $mapperEvent->provider,
        );
    }

    /**
     * @param TransformerFactoryInterface[] $transformerFactories
     */
    public static function create(
        Configuration $configuration,
        PropertyTransformerRegistry $customTransformerRegistry,
        MetadataRegistry $metadataRegistry,
        array $transformerFactories = [],
        ClassMetadataFactory $classMetadataFactory = null,
        AdvancedNameConverterInterface $nameConverter = null,
        ExpressionLanguage $expressionLanguage = new ExpressionLanguage(),
        EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
    ): self {
        // Create property info extractors
        $flags = ReflectionExtractor::ALLOW_PUBLIC;

        if ($configuration->mapPrivateProperties) {
            $flags |= ReflectionExtractor::ALLOW_PRIVATE | ReflectionExtractor::ALLOW_PROTECTED;
        }

        $reflectionExtractor = new ReflectionExtractor(accessFlags: $flags);
        $phpStanExtractor = new PhpStanExtractor();

        if (null !== $nameConverter) {
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new AdvancedNameConverterListener($nameConverter));
        }

        if (null !== $classMetadataFactory) {
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new SerializerMaxDepthListener($classMetadataFactory));
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new SerializerGroupListener($classMetadataFactory));
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new SerializerIgnoreListener($classMetadataFactory));
        }

        $eventDispatcher->addListener(PropertyMetadataEvent::class, new MapToContextListener($reflectionExtractor));
        $eventDispatcher->addListener(GenerateMapperEvent::class, new MapToListener($customTransformerRegistry, $expressionLanguage));
        $eventDispatcher->addListener(GenerateMapperEvent::class, new MapFromListener($customTransformerRegistry, $expressionLanguage));
        $eventDispatcher->addListener(GenerateMapperEvent::class, new MapProviderListener());

        $propertyInfoExtractor = new PropertyInfoExtractor(
            listExtractors: [$reflectionExtractor],
            typeExtractors: [new ReadWriteTypeExtractor(), $phpStanExtractor, $reflectionExtractor],
            accessExtractors: [$reflectionExtractor]
        );

        // Create transformer factories
        $factories = [
            new MultipleTransformerFactory(),
            new NullableTransformerFactory(),
            new UniqueTypeTransformerFactory(),
            new DateTimeTransformerFactory(),
            new BuiltinTransformerFactory(),
            new ArrayTransformerFactory(),
            new ObjectTransformerFactory(),
            new EnumTransformerFactory(),
            new PropertyTransformerFactory($customTransformerRegistry),
            new CopyTransformerFactory(),
        ];

        if (class_exists(AbstractUid::class)) {
            $factories[] = new SymfonyUidTransformerFactory();
        }

        foreach ($transformerFactories as $transformerFactory) {
            $factories[] = $transformerFactory;
        }

        $transformerFactory = new ChainTransformerFactory($factories);

        $sourceTargetMappingExtractor = new SourceTargetMappingExtractor(
            $configuration,
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
        );

        $fromTargetMappingExtractor = new FromTargetMappingExtractor(
            $configuration,
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
        );

        $fromSourceMappingExtractor = new FromSourceMappingExtractor(
            $configuration,
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
        );

        return new self(
            $configuration,
            $sourceTargetMappingExtractor,
            $fromSourceMappingExtractor,
            $fromTargetMappingExtractor,
            $transformerFactory,
            $eventDispatcher,
            $metadataRegistry,
        );
    }
}
