<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Service;

use AutoMapper\Symfony\Attribute\AsAutoMapperExpressionService;

#[AsAutoMapperExpressionService('foo')]
class FooService
{
    public function foo(): string
    {
        return 'foo';
    }
}
