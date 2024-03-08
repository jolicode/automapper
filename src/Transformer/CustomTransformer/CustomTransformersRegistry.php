<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\CustomTransformer;

use AutoMapper\MapperMetadataInterface;
use Symfony\Component\PropertyInfo\Type;

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

    /**
     * @param Type[] $sourceTypes
     * @param Type[] $targetTypes
     *
     * @return array{string, CustomTransformerInterface}|null
     */
    public function getCustomTransformerClass(MapperMetadataInterface $mapperMetadata, array $sourceTypes, array $targetTypes, string $propertyName): ?array
    {
        /**
         * @var string                     $id
         * @var CustomTransformerInterface $customTransformer
         */
        foreach ($this->prioritizedCustomTransformers() as $id => $customTransformer) {
            if (
                ($customTransformer instanceof CustomModelTransformerInterface && $sourceTypes && $targetTypes && $customTransformer->supports($sourceTypes, $targetTypes))
                || $customTransformer instanceof CustomPropertyTransformerInterface && $customTransformer->supports($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $propertyName)
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
