<?php

declare(strict_types=1);

namespace AutoMapper\Tests\ObjectMapper\Fixtures\TransformCollection;

use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Transform\MapCollection;

class TransformCollectionA
{
    #[Map(transform: new MapCollection())]
    /** @var TransformCollectionC[] */
    public array $foo;
}
