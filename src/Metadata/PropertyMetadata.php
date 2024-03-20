<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Transformer\TransformerInterface;

/**
 * Source Property metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @internal
 */
final class PropertyMetadata
{
    public function __construct(
        public readonly SourcePropertyMetadata $source,
        public readonly TargetPropertyMetadata $target,
        public readonly TypesMatching $types,
        public TransformerInterface $transformer,
        public bool $ignored = false,
        public ?int $maxDepth = null,
        public ?string $if = null,
    ) {
    }
}
