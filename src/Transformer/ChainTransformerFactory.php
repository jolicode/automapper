<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\AutoMapperRegistryAwareInterface;
use AutoMapper\AutoMapperRegistryInterface;
use AutoMapper\MapperMetadataInterface;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class ChainTransformerFactory implements TransformerPropertyFactoryInterface, TransformerFactoryInterface, AutoMapperRegistryAwareInterface
{
    protected ?AutoMapperRegistryInterface $autoMapperRegistry = null;

    /**
     * @param array<TransformerFactoryInterface|TransformerPropertyFactoryInterface> $factories
     */
    public function __construct(
        private array $factories = []
    ) {
        foreach ($this->factories as $factory) {
            if ($factory instanceof ChainTransformerFactoryAwareInterface) {
                $factory->setChainTransformerFactory($this);
            }
        }

        $this->sortFactories();
    }

    public function setAutoMapperRegistry(AutoMapperRegistryInterface $autoMapperRegistry): void
    {
        $this->autoMapperRegistry = $autoMapperRegistry;

        foreach ($this->factories as $factory) {
            if ($factory instanceof AutoMapperRegistryAwareInterface) {
                $factory->setAutoMapperRegistry($autoMapperRegistry);
            }
        }
    }

    /**
     * Biggest priority is MultipleTransformerFactory with 128, so default priority will be bigger in order to
     * be used before it, 256 should be enough.
     *
     * @deprecated since 8.2, will be removed in 9.0. Pass the factory into the constructor instead
     */
    public function addTransformerFactory(TransformerFactoryInterface|TransformerPropertyFactoryInterface $transformerFactory, int $priority = 256): void
    {
        trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will be removed in version 9.0, transformer must be injected in the constructor instead.', __METHOD__);

        if ($transformerFactory instanceof AutoMapperRegistryAwareInterface && null !== $this->autoMapperRegistry) {
            $transformerFactory->setAutoMapperRegistry($this->autoMapperRegistry);
        }

        if ($transformerFactory instanceof ChainTransformerFactoryAwareInterface) {
            $transformerFactory->setChainTransformerFactory($this);
        }

        if (!$transformerFactory instanceof PrioritizedTransformerFactoryInterface) {
            /** @var TransformerFactoryInterface|TransformerPropertyFactoryInterface $transformerFactory */
            $transformerFactory = new class($transformerFactory, $priority) implements TransformerFactoryInterface, TransformerPropertyFactoryInterface, PrioritizedTransformerFactoryInterface {
                public function __construct(
                    private readonly TransformerFactoryInterface|TransformerPropertyFactoryInterface $transformerFactory,
                    private readonly int $priority
                ) {
                }

                public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
                {
                    if ($this->transformerFactory instanceof TransformerFactoryInterface) {
                        return $this->transformerFactory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
                    }

                    return null;
                }

                public function getPropertyTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata, string $property): ?TransformerInterface
                {
                    if ($this->transformerFactory instanceof TransformerPropertyFactoryInterface) {
                        return $this->transformerFactory->getPropertyTransformer($sourceTypes, $targetTypes, $mapperMetadata, $property);
                    }

                    return $this->transformerFactory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
                }

                public function getPriority(): int
                {
                    return $this->priority;
                }
            };
        }

        $this->factories[] = $transformerFactory;
        $this->sortFactories();
    }

    /**
     * @deprecated since 8.2, will be removed in 9.0.
     */
    public function hasTransformerFactory(TransformerFactoryInterface $transformerFactory): bool
    {
        trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will be removed in version 9.0, transformer must be injected in the constructor instead.', __METHOD__);

        $this->sortFactories();

        $transformerFactoryClass = $transformerFactory::class;
        foreach ($this->factories as $factory) {
            if (is_a($factory, $transformerFactoryClass)) {
                return true;
            }
        }

        return false;
    }

    public function getPropertyTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata, string $property): ?TransformerInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory instanceof TransformerPropertyFactoryInterface) {
                $transformer = $factory->getPropertyTransformer($sourceTypes, $targetTypes, $mapperMetadata, $property);
            } else {
                $transformer = $factory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);
            }

            if (null !== $transformer) {
                return $transformer;
            }
        }

        return null;
    }

    public function getTransformer(?array $sourceTypes, ?array $targetTypes, MapperMetadataInterface $mapperMetadata): ?TransformerInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory instanceof TransformerFactoryInterface) {
                $transformer = $factory->getTransformer($sourceTypes, $targetTypes, $mapperMetadata);

                if (null !== $transformer) {
                    return $transformer;
                }
            }
        }

        return null;
    }

    private function sortFactories(): void
    {
        usort($this->factories, static function (TransformerPropertyFactoryInterface|TransformerFactoryInterface $a, TransformerPropertyFactoryInterface|TransformerFactoryInterface $b) {
            $aPriority = $a instanceof PrioritizedTransformerFactoryInterface ? $a->getPriority() : 256;
            $bPriority = $b instanceof PrioritizedTransformerFactoryInterface ? $b->getPriority() : 256;

            return $bPriority <=> $aPriority;
        });
    }
}
