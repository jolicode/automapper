<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Transformer\CustomTransformer;

final class FooDependency
{
    public function getBar(): string
    {
        return 'bar';
    }
}
