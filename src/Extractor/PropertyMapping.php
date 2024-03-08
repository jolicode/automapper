<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\MapperGeneratorMetadataInterface;
use AutoMapper\Transformer\TransformerInterface;

/**
 * Property mapping.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final readonly class PropertyMapping
{
    public function __construct(
        public MapperGeneratorMetadataInterface $mapperMetadata,
        public ?ReadAccessor $readAccessor,
        public ?WriteMutator $writeMutator,
        public ?WriteMutator $writeMutatorConstructor,
        public TransformerInterface $transformer,
        public string $property,
        public bool $checkExists = false,
        public ?array $sourceGroups = null,
        public ?array $targetGroups = null,
        public ?int $maxDepth = null,
        public bool $sourceIgnored = false,
        public bool $targetIgnored = false,
        public bool $isPublic = false,
    ) {
    }

    public function shouldIgnoreProperty(bool $shouldMapPrivateProperties = true): bool
    {
        return !$this->writeMutator
            || $this->sourceIgnored
            || $this->targetIgnored
            || !($shouldMapPrivateProperties || $this->isPublic);
    }
}
