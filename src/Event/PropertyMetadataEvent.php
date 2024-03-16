<?php

declare(strict_types=1);

namespace AutoMapper\Event;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Transformer\TransformerInterface;

/**
 * @internal
 */
final class PropertyMetadataEvent
{
    public function __construct(
        public readonly MapperMetadata $mapperMetadata,
        public readonly SourcePropertyMetadata $source,
        public readonly TargetPropertyMetadata $target,
        public ?int $maxDepth = null,
        public ?TransformerInterface $transformer = null,
        public ?bool $ignored = null,
    ) {
    }
}
