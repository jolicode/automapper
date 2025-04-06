<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DiscriminatorPopulate;

use AutoMapper\MapperContext;
use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

#[DiscriminatorMap('linkType', [
    'default' => DefaultLinkType::class,
])]
abstract class LinkType
{
}

class DefaultLinkType extends LinkType
{
    public function __construct(
        public string $link,
        public string $description = 'default',
    ) {
    }
}

class LinkWrapper
{
    public function __construct(
        public LinkType $link,
    ) {
    }
}

$source = [
    'link' => [
        'linkType' => 'default',
        'link' => 'https://example.com/new',
    ],
];

$existingData = new LinkWrapper(new DefaultLinkType('https://example.com/old', 'description'));

return AutoMapperBuilder::buildAutoMapper()->map($source, $existingData, [MapperContext::DEEP_TARGET_TO_POPULATE => true]);
