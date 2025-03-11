<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ObjectWithPropertyAsUnknownArray;

use AutoMapper\Tests\AutoMapperBuilder;

final readonly class ComponentDto
{
    public function __construct(
        public string $name,
    ) {
    }
}

final class Page
{
    public string $title = 'my title';
    public array $components;
}

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

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();
    $entity = new Page();
    $entity->components[] = ['name' => 'my name'];

    yield 'object-with-property-as-unknown-array-to-object' => $autoMapper->map($entity, PageDto::class);

    $dto = new PageDto('my title', [new ComponentDto('my name')]);
    yield 'object-to-object-with-property-as-unknown-array' => $autoMapper->map($dto, Page::class);
})();
