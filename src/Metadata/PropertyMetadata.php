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
    /**
     * @param string[]|null $groups
     */
    public function __construct(
        public readonly SourcePropertyMetadata $source,
        public readonly TargetPropertyMetadata $target,
        public readonly TypesMatching $types,
        public TransformerInterface $transformer,
        public bool $ignored = false,
        public string $ignoreReason = '',
        public ?int $maxDepth = null,
        public ?string $if = null,
        public ?array $groups = null,
        public ?bool $disableGroupsCheck = null,
    ) {
    }
}
