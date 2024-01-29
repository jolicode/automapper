<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Attribute\PropertyAttribute;
use AutoMapper\CustomTransformer\CustomTransformerInterface;
use AutoMapper\MapperMetadata\MapperGeneratorMetadataInterface;
use AutoMapper\Transformer\TransformerInterface;

/**
 * Property mapping.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class PropertyMapping
{
    /** @var TransformerInterface|class-string<CustomTransformerInterface>|null */
    private TransformerInterface|string|null $transformer = null;

    public function __construct(
        public readonly MapperGeneratorMetadataInterface $mapperMetadata,
        public readonly ?ReadAccessor $readAccessor,
        public readonly ?WriteMutator $writeMutator,
        public readonly ?WriteMutator $writeMutatorConstructor,
        public readonly string $property,
        public readonly bool $checkExists = false,
        public readonly ?array $sourceGroups = null,
        public readonly ?array $targetGroups = null,
        public readonly ?int $maxDepth = null,
        public readonly bool $sourceIgnored = false,
        public readonly bool $targetIgnored = false,
        public readonly bool $isPublic = false,
    ) {
    }

    public function shouldIgnoreProperty(bool $shouldMapPrivateProperties = true): bool
    {
        return !$this->writeMutator
            || $this->sourceIgnored
            || $this->targetIgnored
            || !($shouldMapPrivateProperties || $this->isPublic);
    }

    /**
     * @phpstan-assert-if-false TransformerInterface $this->getTransformer()
     *
     * @phpstan-assert-if-true string $this->getTransformer()
     */
    public function hasCustomTransformer(): bool
    {
        return \is_string($this->getTransformer());
    }

    public function setTransformer(TransformerInterface|string $transformer): void
    {
        $this->transformer = $transformer;
    }

    public function getTransformer(): TransformerInterface|string
    {
        if (null === $this->transformer) {
            throw new \LogicException('Transformer not initialized!');
        }

        return $this->transformer;
    }

    /**
     * todo: we should also check in target attribute + check readAccessor (+ write mutators?)
     * todo: may be in an external class?
     */
    public function getRelatedAttribute(): PropertyAttribute|null
    {
        $mapperMetadata = $this->mapperMetadata;

        try {
            $sourceReflectionClass = new \ReflectionClass($mapperMetadata->getSource());

            $reflectionProperty = $sourceReflectionClass->getProperty($this->property);
        } catch (\ReflectionException $e) {
            return null;
        }

        $attributes = $reflectionProperty->getAttributes(PropertyAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
        foreach ($attributes as $attribute) {
            /** @var PropertyAttribute $attributeInstance */
            $attributeInstance = $attribute->newInstance();

            // todo: throw error if multiple attributes?
            if ($attributeInstance->supports($this)) {
                return $attributeInstance;
            }
        }

        return null;
    }
}
