<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;

/**
 * @internal
 */
final class PropertyTransformerRegistry
{
    /** @var array<string, PropertyTransformerInterface> */
    private readonly array $propertyTransformers;

    /** @var array<string, PropertyTransformerInterface>|null */
    private ?array $prioritizedPropertyTransformers = null;

    /**
     * @param iterable<string|int, PropertyTransformerInterface> $propertyTransformers
     */
    public function __construct(iterable $propertyTransformers)
    {
        $indexedPropertyTransformers = [];

        foreach ($propertyTransformers as $key => $propertyTransformer) {
            if (\is_int($key)) {
                $key = $propertyTransformer::class;
            }

            $indexedPropertyTransformers[$key] = $propertyTransformer;
        }

        $this->propertyTransformers = $indexedPropertyTransformers;
    }

    /**
     * @return array<string, PropertyTransformerInterface>
     */
    public function getPropertyTransformers(): array
    {
        return $this->propertyTransformers;
    }

    public function getPropertyTransformer(string $id): ?PropertyTransformerInterface
    {
        return $this->propertyTransformers[$id] ?? null;
    }

    public function getPropertyTransformersForMapper(TypesMatching $types, SourcePropertyMetadata $sourceProperty, TargetPropertyMetadata $targetProperty, MapperMetadata $mapperMetadata): ?string
    {
        foreach ($this->prioritizedPropertyTransformers() as $id => $propertyTransformer) {
            if ($propertyTransformer instanceof PropertyTransformerSupportInterface && $propertyTransformer->supports($types, $sourceProperty, $targetProperty, $mapperMetadata)) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return array<string, PropertyTransformerInterface>
     */
    private function prioritizedPropertyTransformers(): array
    {
        if (null === $this->prioritizedPropertyTransformers) {
            $this->prioritizedPropertyTransformers = $this->propertyTransformers;

            uasort(
                $this->prioritizedPropertyTransformers,
                static function (PropertyTransformerInterface $a, PropertyTransformerInterface $b): int {
                    $aPriority = $a instanceof PrioritizedPropertyTransformerInterface ? $a->getPriority() : 0;
                    $bPriority = $b instanceof PrioritizedPropertyTransformerInterface ? $b->getPriority() : 0;

                    return $bPriority <=> $aPriority;
                }
            );
        }

        return $this->prioritizedPropertyTransformers;
    }
}
