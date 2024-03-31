<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Normalizer;

use Symfony\Component\Serializer\Attribute\Groups;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
final class ChildOfGroupsAnnotationDummy extends Groups
{
    public function __construct()
    {
        parent::__construct(['d']);
    }
}
