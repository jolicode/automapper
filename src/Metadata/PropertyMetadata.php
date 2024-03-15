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
    public ?TransformerInterface $transformer = null;

    public function __construct(
        public readonly SourcePropertyMetadata $source,
        public readonly TargetPropertyMetadata $target,
        public ?int $maxDepth = null,
    ) {
    }

    public function shouldIgnoreProperty(bool $shouldMapPrivateProperties = true): bool
    {
        return !$this->target->writeMutator
            || $this->source->ignored
            || $this->target->ignored
            || !($shouldMapPrivateProperties || $this->source->isPublic);
    }
}
