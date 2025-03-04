<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\ObjectWithPropertyAsUnknownArray;

final readonly class PageDto
{
    /**
     * @param list<ComponentDto> $components
     */
    public function __construct(
        public string $title,
        public array $components,
    ) {
    }
}
