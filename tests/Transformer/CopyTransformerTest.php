<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\CopyTransformer;
use PHPUnit\Framework\TestCase;

class CopyTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testCopyTransformer(): void
    {
        $transformer = new CopyTransformer();

        $output = $this->evalTransformer($transformer, 'foo');

        self::assertSame('foo', $output);
    }
}
