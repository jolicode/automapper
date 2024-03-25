<?php

declare(strict_types=1);

namespace AutoMapper\Event;

use AutoMapper\Metadata\MapperMetadata;

/**
 * @internal
 */
final class GenerateMapperEvent
{
    /**
     * @param PropertyMetadataEvent[] $properties A list of properties to add to this mapping
     */
    public function __construct(
        public readonly MapperMetadata $mapperMetadata,
        public array $properties = [],
        public ?string $provider = null,
    ) {
    }
}
