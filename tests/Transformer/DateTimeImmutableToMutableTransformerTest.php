<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\DateTimeImmutableToMutableTransformer;
use PHPUnit\Framework\TestCase;

class DateTimeImmutableToMutableTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testDateTimeImmutableTransformer(): void
    {
        $transformer = new DateTimeImmutableToMutableTransformer();

        $date = new \DateTimeImmutable();
        $output = $this->evalTransformer($transformer, $date);

        self::assertInstanceOf(\DateTime::class, $output);
        self::assertSame($date->format(\DateTime::RFC3339), $output->format(\DateTime::RFC3339));
    }
}
