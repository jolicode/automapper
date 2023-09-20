<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Transformer\TransformerInterface;

/**
 * Property mapping.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 */
final class PropertyMapping
{
    public function __construct(
        public readonly ReadAccessor $readAccessor,
        public readonly ?WriteMutator $writeMutator,
        public readonly ?WriteMutator $writeMutatorConstructor,
        public readonly TransformerInterface $transformer,
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
        return $this->sourceIgnored
            || $this->targetIgnored
            || !($shouldMapPrivateProperties || $this->isPublic);
    }
}
