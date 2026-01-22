<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\Fixtures\Transformer\CustomTransformer\InvokableTransformer;

class FooTransformerObjectCallable
{
    #[MapTo(target: 'array', transformer: new InvokableTransformer('test'))]
    public string $foo;
}
