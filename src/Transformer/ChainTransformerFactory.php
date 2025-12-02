<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;

/**
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
class ChainTransformerFactory implements TransformerFactoryInterface
{
    /**
     * @param array<TransformerFactoryInterface> $factories
     */
    public function __construct(
        private array $factories = [],
    ) {
        foreach ($this->factories as $factory) {
            if ($factory instanceof ChainTransformerFactoryAwareInterface) {
                $factory->setChainTransformerFactory($this);
            }
        }

        $this->sortFactories();
    }

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory instanceof TransformerFactoryInterface) {
                $transformer = $factory->getTransformer($source, $target, $mapperMetadata);

                if (null !== $transformer) {
                    return $transformer;
                }
            }
        }

        return null;
    }

    private function sortFactories(): void
    {
        usort($this->factories, static function (TransformerFactoryInterface $a, TransformerFactoryInterface $b) {
            $aPriority = $a instanceof PrioritizedTransformerFactoryInterface ? $a->getPriority() : 256;
            $bPriority = $b instanceof PrioritizedTransformerFactoryInterface ? $b->getPriority() : 256;

            return $bPriority <=> $aPriority;
        });
    }
}
