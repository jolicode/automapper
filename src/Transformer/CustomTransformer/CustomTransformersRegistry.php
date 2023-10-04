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
    /** @var list<CustomTransformerInterface> */
    private array $customTransformers = [];

    /** @var list<CustomTransformerInterface>|null */
    private array|null $prioritizedCustomTransformers = null;

    public function addCustomTransformer(CustomTransformerInterface $customTransformer): void
    {
        if (!\in_array($customTransformer, $this->customTransformers, true)) {
            $this->customTransformers[] = $customTransformer;
        }
    }

    /**
     * @param Type[] $sourceTypes
     * @param Type[] $targetTypes
     *
     * @return class-string<CustomTransformerInterface>|null
     */
    public function getCustomTransformerClass(MapperMetadataInterface $mapperMetadata, array $sourceTypes, array $targetTypes, string $propertyName): string|null
    {
        foreach ($this->prioritizedCustomTransformers() as $customTransformer) {
            if (
                $customTransformer instanceof CustomModelTransformerInterface && $customTransformer->supports($sourceTypes, $targetTypes)
                || $customTransformer instanceof CustomPropertyTransformerInterface && $customTransformer->supports($mapperMetadata->getSource(), $mapperMetadata->getTarget(), $propertyName)
            ) {
                return $customTransformer::class;
            }
        }

        return null;
    }

    /**
     * @return list<CustomTransformerInterface>
     */
    private function prioritizedCustomTransformers(): array
    {
        if (null === $this->prioritizedCustomTransformers) {
            $this->prioritizedCustomTransformers = $this->customTransformers;

            usort(
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
