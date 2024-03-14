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
        public TransformerInterface $transformer,
        public bool $isIgnored = false,
        public ?int $maxDepth = null,
    ) {
    }

    public function shouldIgnoreProperty(): bool
    {
        return !$this->target->writeMutator || $this->isIgnored;
    }
}
