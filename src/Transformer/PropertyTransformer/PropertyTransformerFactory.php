<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\PrioritizedTransformerFactoryInterface;
use AutoMapper\Transformer\TransformerFactoryInterface;
use AutoMapper\Transformer\TransformerInterface;

/**
 * @internal
 */
final class PropertyTransformerFactory implements PrioritizedTransformerFactoryInterface, TransformerFactoryInterface
{
    /** @var array<string, PropertyTransformerSupportInterface>|null */
    private $prioritizedPropertyTransformers;

    public function __construct(
        /** @var iterable<string, PropertyTransformerSupportInterface> */
        private readonly iterable $propertyTransformersSupportList,
    ) {
    }

    public function getPriority(): int
    {
        return 256;
    }

    public function getTransformer(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): ?TransformerInterface
    {
        foreach ($this->prioritizedPropertyTransformers() as $id => $propertyTransformer) {
            if ($propertyTransformer instanceof PropertyTransformerSupportInterface && $propertyTransformer->supports($source, $target, $mapperMetadata)) {
                return new PropertyTransformer($id);
            }
        }

        return null;
    }

    /**
     * @return array<string, PropertyTransformerSupportInterface>
     */
    private function prioritizedPropertyTransformers(): array
    {
        if (null === $this->prioritizedPropertyTransformers) {
            $this->prioritizedPropertyTransformers = iterator_to_array($this->propertyTransformersSupportList);

            uasort(
                $this->prioritizedPropertyTransformers,
                static function (PropertyTransformerSupportInterface $a, PropertyTransformerSupportInterface $b): int {
                    $aPriority = $a instanceof PrioritizedPropertyTransformerInterface ? $a->getPriority() : 0;
                    $bPriority = $b instanceof PrioritizedPropertyTransformerInterface ? $b->getPriority() : 0;

                    return $bPriority <=> $aPriority;
                }
            );
        }

        return $this->prioritizedPropertyTransformers;
    }
}
