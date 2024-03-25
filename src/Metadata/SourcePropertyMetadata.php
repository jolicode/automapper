<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Event\SourcePropertyMetadata as SourcePropertyMetadataEvent;
use AutoMapper\Extractor\ReadAccessor;

/**
 * Source Property metadata.
 *
 * @author Joel Wurtz <jwurtz@jolicode.com>
 *
 * @experimental
 */
final readonly class SourcePropertyMetadata
{
    /**
     * @param string[]|null $groups
     */
    public function __construct(
        public string $name,
        public ?ReadAccessor $accessor = null,
        public bool $checkExists = false,
        public ?array $groups = null,
        public string $dateTimeFormat = \DateTimeInterface::RFC3339,
    ) {
    }

    public static function fromEvent(SourcePropertyMetadataEvent $metadata): self
    {
        return new self(
            $metadata->name,
            $metadata->accessor,
            $metadata->checkExists ?? false,
            $metadata->groups,
            $metadata->dateTimeFormat ?? \DateTimeInterface::RFC3339,
        );
    }
}
