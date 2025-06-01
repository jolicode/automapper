<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Configuration;
use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Event\SourcePropertyMetadata as SourcePropertyMetadataEvent;
use AutoMapper\Event\TargetPropertyMetadata as TargetPropertyMetadataEvent;
use AutoMapper\EventListener\MapFromListener;
use AutoMapper\EventListener\MapperListener;
use AutoMapper\EventListener\MapProviderListener;
use AutoMapper\EventListener\MapToContextListener;
use AutoMapper\EventListener\MapToListener;
use AutoMapper\EventListener\Symfony\AdvancedNameConverterListener;
use AutoMapper\EventListener\Symfony\ClassDiscriminatorListener;
use AutoMapper\EventListener\Symfony\SerializerGroupListener;
use AutoMapper\EventListener\Symfony\SerializerIgnoreListener;
use AutoMapper\EventListener\Symfony\SerializerMaxDepthListener;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Extractor\ReadWriteTypeExtractor;
use AutoMapper\Extractor\SourceTargetMappingExtractor;
use AutoMapper\Extractor\WriteMutator;
use AutoMapper\Generator\Shared\ClassDiscriminatorResolver;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use AutoMapper\Transformer\ArrayTransformerFactory;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\CopyTransformerFactory;
use AutoMapper\Transformer\DateTimeTransformerFactory;
use AutoMapper\Transformer\DependentTransformerInterface;
use AutoMapper\Transformer\DoctrineCollectionTransformerFactory;
use AutoMapper\Transformer\EnumTransformerFactory;
use AutoMapper\Transformer\MapperDependency;
use AutoMapper\Transformer\MultipleTransformerFactory;
use AutoMapper\Transformer\NullableTransformerFactory;
use AutoMapper\Transformer\ObjectTransformerFactory;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerFactory;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerRegistry;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\UniqueTypeTransformerFactory;
use AutoMapper\Transformer\VoidTransformer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
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
        private readonly ClassDiscriminatorResolver $classDiscriminatorResolver,
        private readonly bool $removeDefaultProperties = false,
    ) {
        if (!$this->removeDefaultProperties) {
            trigger_deprecation('jolicode/automapper', '9.4', 'Not removing default properties is deprecated, pass this parameter to true and add necessary attributes if needed', __CLASS__);
        }
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

            // Add dependencies from transformer to the mapper
            foreach ($metadata->propertiesMetadata as $propertyMapping) {
                if ($propertyMapping->transformer instanceof DependentTransformerInterface) {
                    foreach ($propertyMapping->transformer->getDependencies() as $mapperDependency) {
                        $dependencyMetadata = $this->getGeneratorMetadata($mapperDependency->source, $mapperDependency->target);

                        $metadata->addDependency(new Dependency($mapperDependency, $dependencyMetadata));
                    }
                }
            }

            // Add dependencies from discriminator to the mapper
            if ($this->classDiscriminatorResolver->hasClassDiscriminator($metadata, true)) {
                foreach ($this->classDiscriminatorResolver->discriminatorMapperNames($metadata, true) as $newSourceType => $mapperDependencyName) {
                    $dependencyMetadata = $this->getGeneratorMetadata($newSourceType, $metadata->mapperMetadata->target);
                    $mapperDependency = new MapperDependency($mapperDependencyName, $newSourceType, $metadata->mapperMetadata->target);

                    $metadata->addDependency(new Dependency($mapperDependency, $dependencyMetadata));
                }
            }

            if ($this->classDiscriminatorResolver->hasClassDiscriminator($metadata, false)) {
                foreach ($this->classDiscriminatorResolver->discriminatorMapperNames($metadata, false) as $newTargetType => $mapperDependencyName) {
                    $dependencyMetadata = $this->getGeneratorMetadata($metadata->mapperMetadata->source, $newTargetType);
                    $mapperDependency = new MapperDependency($mapperDependencyName, $metadata->mapperMetadata->source, $newTargetType);

                    $metadata->addDependency(new Dependency($mapperDependency, $dependencyMetadata));
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

            // Add dependencies from discriminator to the mapper
            if ($this->classDiscriminatorResolver->hasClassDiscriminator($generatorMetadata, true)) {
                foreach ($this->classDiscriminatorResolver->discriminatorMapperNames($generatorMetadata, true) as $newSourceType => $mapperDependencyName) {
                    $remainingMetadata[] = $metadataRegistry->get($newSourceType, $generatorMetadata->mapperMetadata->target);
                }
            }

            if ($this->classDiscriminatorResolver->hasClassDiscriminator($generatorMetadata, false)) {
                foreach ($this->classDiscriminatorResolver->discriminatorMapperNames($generatorMetadata, false) as $newTargetType => $mapperDependencyName) {
                    $remainingMetadata[] = $metadataRegistry->get($generatorMetadata->mapperMetadata->source, $newTargetType);
                }
            }
        }
    }

    /**
     * @return iterable<GeneratorMetadata>
     */
    public function listMetadata(): iterable
    {
        foreach ($this->generatorMetadata as $targets) {
            foreach ($targets as $metadata) {
                yield $metadata;
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
            $propertyEvent = new PropertyMetadataEvent($mapperMetadata, new SourcePropertyMetadataEvent($property), new TargetPropertyMetadataEvent($property), isFromDefaultExtractor: true);

            $this->eventDispatcher->dispatch($propertyEvent);

            $propertyEvents[$propertyEvent->target->property] = $propertyEvent;
        }

        foreach ($extractor->getProperties($mapperMetadata->target, withConstructorParameters: true) as $property) {
            if (isset($propertyEvents[$property])) {
                continue;
            }

            $propertyEvent = new PropertyMetadataEvent($mapperMetadata, new SourcePropertyMetadataEvent($property), new TargetPropertyMetadataEvent($property), isFromDefaultExtractor: true);

            $this->eventDispatcher->dispatch($propertyEvent);

            $propertyEvents[$propertyEvent->target->property] = $propertyEvent;
        }

        foreach ($mapperEvent->properties as $propertyEvent) {
            $this->eventDispatcher->dispatch($propertyEvent);

            if ($this->removeDefaultProperties) {
                foreach ($propertyEvents as $propertyEventExisting) {
                    if ($propertyEventExisting->source->property === $propertyEvent->source->property && $propertyEventExisting->isFromDefaultExtractor && !$propertyEventExisting->ignored) {
                        $propertyEventExisting->ignored = true;
                        $propertyEventExisting->ignoreReason = 'Default property is ignored because a custom property is defined.';
                    }
                }
            }

            $propertyEvents[$propertyEvent->target->property] = $propertyEvent;
        }

        // Sort transformations by property name, to ensure consistent order, and easier debugging
        ksort($propertyEvents, SORT_NATURAL);

        $propertiesMapping = [];

        foreach ($propertyEvents as $propertyMappedEvent) {
            // Create the source property metadata
            if ($propertyMappedEvent->source->accessor === null) {
                $propertyMappedEvent->source->accessor = $extractor->getReadAccessor($mapperMetadata->source, $propertyMappedEvent->source->property, $mapperEvent->allowExtraProperties ?? $this->configuration->allowExtraProperties);
            }

            if ($propertyMappedEvent->source->checkExists === null) {
                $propertyMappedEvent->source->checkExists = $extractor->getCheckExists($mapperMetadata->source, $propertyMappedEvent->source->property);
            }

            if ($propertyMappedEvent->source->extractGroupsIfNull && $propertyMappedEvent->source->groups === null) {
                $propertyMappedEvent->source->groups = $extractor->getGroups($mapperMetadata->source, $propertyMappedEvent->source->property);
            }

            if ($propertyMappedEvent->source->dateTimeFormat === null) {
                $propertyMappedEvent->source->dateTimeFormat = $extractor->getDateTimeFormat($propertyMappedEvent);
            }

            // Create the target property metadata
            if ($propertyMappedEvent->target->readAccessor === null) {
                $propertyMappedEvent->target->readAccessor = $extractor->getReadAccessor($mapperMetadata->target, $propertyMappedEvent->target->property, $mapperEvent->allowExtraProperties ?? $this->configuration->allowExtraProperties);
            }

            if ($propertyMappedEvent->target->writeMutator === null) {
                $propertyMappedEvent->target->writeMutator = $extractor->getWriteMutator($mapperMetadata->source, $mapperMetadata->target, $propertyMappedEvent->target->property, [
                    'enable_constructor_extraction' => false,
                ], $mapperEvent->allowExtraProperties ?? $this->configuration->allowExtraProperties);
            }

            if ($propertyMappedEvent->target->parameterInConstructor === null) {
                $mutator = $extractor->getWriteMutator($mapperMetadata->source, $mapperMetadata->target, $propertyMappedEvent->target->property, [
                    'enable_constructor_extraction' => true,
                ]);

                if ($mutator !== null && $mutator->type === WriteMutator::TYPE_CONSTRUCTOR && $mutator->parameter !== null) {
                    $propertyMappedEvent->target->parameterInConstructor = $mutator->parameter->getName();
                }
            }

            if ($propertyMappedEvent->target->extractGroupsIfNull && $propertyMappedEvent->target->groups === null) {
                $propertyMappedEvent->target->groups = $extractor->getGroups($mapperMetadata->target, $propertyMappedEvent->target->property);
            }

            if ($propertyMappedEvent->target->dateTimeFormat === null) {
                $propertyMappedEvent->target->dateTimeFormat = $extractor->getDateTimeFormat($propertyMappedEvent);
            }

            $sourcePropertyMetadata = SourcePropertyMetadata::fromEvent($propertyMappedEvent->source);
            $targetPropertyMetadata = TargetPropertyMetadata::fromEvent($propertyMappedEvent->target);

            if (null === $propertyMappedEvent->types) {
                $propertyMappedEvent->types = $extractor->getTypes($mapperMetadata->source, $sourcePropertyMetadata, $mapperMetadata->target, $targetPropertyMetadata, $propertyMappedEvent->extractTypesFromGetter ?? $this->configuration->extractTypesFromGetter);
            }

            if (null === $propertyMappedEvent->transformer) {
                $transformer = $this->transformerFactory->getTransformer($propertyMappedEvent->types, $sourcePropertyMetadata, $targetPropertyMetadata, $mapperMetadata);

                if (null === $transformer) {
                    $propertyMappedEvent->ignored = true;
                    $propertyMappedEvent->ignoreReason = 'We didn\'t find a way to correctly transform this property.';
                }

                $propertyMappedEvent->transformer = $transformer;
            }

            if ($sourcePropertyMetadata->accessor === null && !($propertyMappedEvent->transformer instanceof AllowNullValueTransformerInterface)) {
                $propertyMappedEvent->ignored = true;

                if ($propertyMappedEvent->transformer === null) {
                    $propertyMappedEvent->ignoreReason = 'Property cannot be read from source.';
                } else {
                    $propertyMappedEvent->ignoreReason = 'Property cannot be read from source, and the attached transformer `' . $propertyMappedEvent->transformer::class . '` require a value.';
                }
            }

            if ($targetPropertyMetadata->writeMutator === null && $targetPropertyMetadata->parameterInConstructor === null) {
                $propertyMappedEvent->ignored = true;
                $propertyMappedEvent->ignoreReason = 'Property cannot be write on target.';
            }

            $propertiesMapping[] = new PropertyMetadata(
                $sourcePropertyMetadata,
                $targetPropertyMetadata,
                $propertyMappedEvent->types,
                $propertyMappedEvent->transformer ?? new VoidTransformer(),
                $propertyMappedEvent->ignored ?? false,
                $propertyMappedEvent->ignoreReason ?? '',
                $propertyMappedEvent->maxDepth,
                $propertyMappedEvent->if,
                $propertyMappedEvent->groups,
                $propertyMappedEvent->disableGroupsCheck,
                $propertyMappedEvent->identifier ?? false,
            );
        }

        return new GeneratorMetadata(
            $mapperMetadata,
            $propertiesMapping,
            $mapperEvent->checkAttributes ?? $this->configuration->attributeChecking,
            $mapperEvent->constructorStrategy ?? $this->configuration->constructorStrategy,
            $mapperEvent->allowReadOnlyTargetToPopulate ?? $this->configuration->allowReadOnlyTargetToPopulate,
            $mapperEvent->strictTypes ?? $this->configuration->strictTypes,
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
        ClassDiscriminatorResolver $classDiscriminatorResolver,
        array $transformerFactories = [],
        ?ClassMetadataFactory $classMetadataFactory = null,
        AdvancedNameConverterInterface|NameConverterInterface|null $nameConverter = null,
        ExpressionLanguage $expressionLanguage = new ExpressionLanguage(),
        EventDispatcherInterface $eventDispatcher = new EventDispatcher(),
        bool $removeDefaultProperties = false,
    ): self {
        // Create property info extractors
        $flags = ReflectionExtractor::ALLOW_PUBLIC;

        if ($configuration->mapPrivateProperties) {
            $flags |= ReflectionExtractor::ALLOW_PRIVATE | ReflectionExtractor::ALLOW_PROTECTED;
        }

        $reflectionExtractor = new ReflectionExtractor(accessFlags: $flags);
        $phpStanExtractor = new PhpStanExtractor();

        if (null !== $classMetadataFactory) {
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new AdvancedNameConverterListener(new MetadataAwareNameConverter($classMetadataFactory, $nameConverter)));
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new SerializerMaxDepthListener($classMetadataFactory));
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new SerializerGroupListener($classMetadataFactory));
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new SerializerIgnoreListener($classMetadataFactory));
            $eventDispatcher->addListener(GenerateMapperEvent::class, new ClassDiscriminatorListener(new ClassDiscriminatorFromClassMetadata($classMetadataFactory)));
        } elseif (null !== $nameConverter) {
            $eventDispatcher->addListener(PropertyMetadataEvent::class, new AdvancedNameConverterListener($nameConverter));
        }

        $eventDispatcher->addListener(PropertyMetadataEvent::class, new MapToContextListener($reflectionExtractor));
        $eventDispatcher->addListener(GenerateMapperEvent::class, new MapToListener($customTransformerRegistry, $expressionLanguage));
        $eventDispatcher->addListener(GenerateMapperEvent::class, new MapFromListener($customTransformerRegistry, $expressionLanguage));
        $eventDispatcher->addListener(GenerateMapperEvent::class, new MapperListener());
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
            new DoctrineCollectionTransformerFactory(),
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
            $classDiscriminatorResolver,
            $removeDefaultProperties,
        );
    }
}
