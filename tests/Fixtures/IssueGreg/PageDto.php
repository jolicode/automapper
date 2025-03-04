<?php

namespace AutoMapper\Tests\Fixtures\IssueGreg;

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
