<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Configuration;
use AutoMapper\Extractor\FromSourceMappingExtractor;
use AutoMapper\Extractor\FromTargetMappingExtractor;
use AutoMapper\Extractor\MapToContextPropertyInfoExtractorDecorator;
use AutoMapper\Extractor\SourceTargetMappingExtractor;
use AutoMapper\Transformer\ArrayTransformerFactory;
use AutoMapper\Transformer\BuiltinTransformerFactory;
use AutoMapper\Transformer\ChainTransformerFactory;
use AutoMapper\Transformer\CustomTransformer\CustomTransformerFactory;
use AutoMapper\Transformer\CustomTransformer\CustomTransformersRegistry;
use AutoMapper\Transformer\DateTimeTransformerFactory;
use AutoMapper\Transformer\DependentTransformerInterface;
use AutoMapper\Transformer\EnumTransformerFactory;
use AutoMapper\Transformer\MultipleTransformerFactory;
use AutoMapper\Transformer\NullableTransformerFactory;
use AutoMapper\Transformer\ObjectTransformerFactory;
use AutoMapper\Transformer\SymfonyUidTransformerFactory;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\UniqueTypeTransformerFactory;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Uid\AbstractUid;

/**
 * @internal
 */
final class MetadataRegistry
{
    /** @var array<string, array<string, MapperMetadata>> */
    private array $mapperMetadata = [];

    /** @var array<string, array<string, GeneratorMetadata>> */
    private array $generatorMetadata = [];

    public function __construct(
        private readonly Configuration $configuration,
        private readonly SourceTargetMappingExtractor $sourceTargetPropertiesMappingExtractor,
        private readonly FromSourceMappingExtractor $fromSourcePropertiesMappingExtractor,
        private readonly FromTargetMappingExtractor $fromTargetPropertiesMappingExtractor,
        private readonly TransformerFactoryInterface $transformerFactory,
        private readonly string $classPrefix = 'Mapper_',
    ) {
    }

    /**
     * @param class-string<object>|'array' $source
     * @param class-string<object>|'array' $target
     *
     * @internal
     */
    public function getMapperMetadata(string $source, string $target): MapperMetadata
    {
        if (!isset($this->mapperMetadata[$source][$target])) {
            $this->mapperMetadata[$source][$target] = new MapperMetadata($source, $target, $this->classPrefix);
        }

        return $this->mapperMetadata[$source][$target];
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
            $metadata = $this->createGeneratorMetadata($this->getMapperMetadata($source, $target));
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

    private function createGeneratorMetadata(MapperMetadata $mapperMetadata): GeneratorMetadata
    {
        $extractor = $this->sourceTargetPropertiesMappingExtractor;

        if ('array' === $mapperMetadata->source || 'stdClass' === $mapperMetadata->source) {
            $extractor = $this->fromTargetPropertiesMappingExtractor;
        }

        if ('array' === $mapperMetadata->target || 'stdClass' === $mapperMetadata->target) {
            $extractor = $this->fromSourcePropertiesMappingExtractor;
        }

        $propertiesMapping = [];

        foreach ($extractor->getPropertiesMapping($mapperMetadata) as $propertyMapping) {
            // @TODO Here do event to allow to change the property mapping before getting the transformer

            if (null === $propertyMapping->transformer) {
                $transformer = $this->transformerFactory->getTransformer($propertyMapping->source, $propertyMapping->target, $mapperMetadata);

                if (null === $transformer) {
                    continue;
                }

                $propertyMapping->transformer = $transformer;
            }

            $propertiesMapping[] = $propertyMapping;
        }

        return new GeneratorMetadata($mapperMetadata, $propertiesMapping, $this->configuration->attributeChecking, $this->configuration->allowConstructor);
    }

    /**
     * @param TransformerFactoryInterface[] $transformerFactories
     */
    public static function create(
        Configuration $configuration,
        CustomTransformersRegistry $customTransformerRegistry,
        ClassMetadataFactory $classMetadataFactory,
        AdvancedNameConverterInterface $nameConverter = null,
        array $transformerFactories = [],
    ): self {
        // Create property info extractors
        $flags = ReflectionExtractor::ALLOW_PUBLIC;

        if ($configuration->mapPrivateProperties) {
            $flags |= ReflectionExtractor::ALLOW_PRIVATE | ReflectionExtractor::ALLOW_PROTECTED;
        }

        $reflectionExtractor = new ReflectionExtractor(accessFlags: $flags);
        $phpStanExtractor = new PhpStanExtractor();

        $propertyInfoExtractor = new PropertyInfoExtractor(
            listExtractors: [$reflectionExtractor],
            typeExtractors: [$phpStanExtractor, $reflectionExtractor],
            accessExtractors: [new MapToContextPropertyInfoExtractorDecorator($reflectionExtractor)]
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
            new CustomTransformerFactory($customTransformerRegistry),
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
            new MapToContextPropertyInfoExtractorDecorator($reflectionExtractor),
            $reflectionExtractor,
            $classMetadataFactory
        );

        $fromTargetMappingExtractor = new FromTargetMappingExtractor(
            $configuration,
            $propertyInfoExtractor,
            $reflectionExtractor,
            $reflectionExtractor,
            $classMetadataFactory,
            $nameConverter
        );

        $fromSourceMappingExtractor = new FromSourceMappingExtractor(
            $configuration,
            $propertyInfoExtractor,
            new MapToContextPropertyInfoExtractorDecorator($reflectionExtractor),
            $reflectionExtractor,
            $classMetadataFactory,
            $nameConverter
        );

        return new self(
            $configuration,
            $sourceTargetMappingExtractor,
            $fromSourceMappingExtractor,
            $fromTargetMappingExtractor,
            $transformerFactory,
            $configuration->classPrefix,
        );
    }
}
