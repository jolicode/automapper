<?php

declare(strict_types=1);

namespace AutoMapper\Metadata;

use AutoMapper\Event\SourcePropertyMetadata as SourcePropertyMetadataEvent;
use AutoMapper\Extractor\ReadAccessor;
use Symfony\Component\TypeInfo\Type;

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
        public string $property,
        public ?ReadAccessor $accessor = null,
        public bool $checkExists = false,
        public ?array $groups = null,
        public string $dateTimeFormat = \DateTimeInterface::RFC3339,
        public ?Type $type = null,
    ) {
    }

    public static function fromEvent(SourcePropertyMetadataEvent $metadata): self
    {
        return new self(
            $metadata->property,
            $metadata->accessor,
            $metadata->checkExists ?? false,
            $metadata->groups,
            $metadata->dateTimeFormat ?? \DateTimeInterface::RFC3339,
        );
    }

    public function withType(?Type $type): self
    {
        return new self(
            $this->property,
            $this->accessor,
            $this->checkExists,
            $this->groups,
            $this->dateTimeFormat,
            $type,
        );
    }
}
