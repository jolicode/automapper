<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\TypesMatching;

/**
 * @internal
 */
final class CustomTransformersRegistry
{
    /** @var array<string, CustomTransformerInterface> */
    private array $customTransformers = [];

    /** @var array<string, CustomTransformerInterface>|null */
    private ?array $prioritizedCustomTransformers = null;

    /**
     * @return array<string, CustomTransformerInterface>
     */
    public function getCustomTransformers(): array
    {
        return $this->customTransformers;
    }

    public function addCustomTransformer(CustomTransformerInterface $customTransformer, ?string $id = null): void
    {
        $id = $id ?? $customTransformer::class;
        $this->customTransformers[$id] = $customTransformer;
    }

    public function getCustomTransformer(string $id): ?CustomTransformerInterface
    {
        return $this->customTransformers[$id] ?? null;
    }

    /**
     * @return array{string, CustomTransformerInterface}|null
     */
    public function getCustomTransformerClass(MapperMetadata $mapperMetadata, TypesMatching $types, string $sourceProperty, string $targetProperty): ?array
    {
        /**
         * @var string                     $id
         * @var CustomTransformerInterface $customTransformer
         */
        foreach ($this->prioritizedCustomTransformers() as $id => $customTransformer) {
            if (
                ($customTransformer instanceof CustomModelTransformerInterface && $customTransformer->supports($types))
                || ($customTransformer instanceof CustomPropertyTransformerInterface && $customTransformer->supports($mapperMetadata->source, $mapperMetadata->target, $sourceProperty, $targetProperty))
            ) {
                return [$id, $customTransformer];
            }
        }

        return null;
    }

    /**
     * @return array<string, CustomTransformerInterface>
     */
    private function prioritizedCustomTransformers(): array
    {
        if (null === $this->prioritizedCustomTransformers) {
            $this->prioritizedCustomTransformers = $this->customTransformers;

            uasort(
                $this->prioritizedCustomTransformers,
                static function (CustomTransformerInterface $a, CustomTransformerInterface $b): int {
                    $aPriority = $a instanceof PrioritizedCustomTransformerInterface ? $a->getPriority() : 0;
                    $bPriority = $b instanceof PrioritizedCustomTransformerInterface ? $b->getPriority() : 0;

                    return $bPriority <=> $aPriority;
                }
            );
        }

        return $this->prioritizedCustomTransformers;
    }
}
