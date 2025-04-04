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
        public ?string $dateTimeFormat = null,
        public ?bool $ignored = null,
        public ?string $ignoreReason = null,
        public ?string $if = null,
        public ?array $groups = null,
        public ?bool $disableGroupsCheck = null,
        public int $priority = 0,
        public readonly bool $isFromDefaultExtractor = false,
        public ?bool $extractTypesFromGetter = null,
        public ?bool $identifier = null,
    ) {
    }
}
