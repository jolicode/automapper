<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\DiscriminatorMapBadConfiguration;

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
    ) {
    }
}

$source = [
    // There is a typo on the key `type`.
    // It should be `linkType` instead of `type`.
    'type' => 'default',
    'link' => 'https://example.com',
];

try {
    return AutoMapperBuilder::buildAutoMapper()->map($source, LinkType::class);
} catch (\Throwable $th) {
    return $th;
}
