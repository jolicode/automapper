<?php

declare(strict_types=1);

namespace AutoMapper\Event;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Transformer\TransformerInterface;

/**
 * @internal
 */
final class PropertyMetadataEvent
{
    /**
     * @param string[]|null $groups
     */
    public function __construct(
        public readonly MapperMetadata $mapperMetadata,
        public readonly SourcePropertyMetadata $source,
        public readonly TargetPropertyMetadata $target,
        public ?TypesMatching $types = null,
        public ?int $maxDepth = null,
        public ?TransformerInterface $transformer = null,
        public ?bool $ignored = null,
        public ?string $if = null,
        public ?array $groups = null,
    ) {
    }
}
