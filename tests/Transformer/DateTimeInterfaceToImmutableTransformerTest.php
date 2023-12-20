<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\DateTimeInterfaceToImmutableTransformer;
use PHPUnit\Framework\TestCase;

class DateTimeInterfaceToImmutableTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testDateTimeImmutableTransformer(): void
    {
        $transformer = new DateTimeInterfaceToImmutableTransformer();

        $date = new \DateTime();
        $output = $this->evalTransformer($transformer, $date);

        self::assertInstanceOf(\DateTimeImmutable::class, $output);
        self::assertSame($date->format(\DateTime::RFC3339), $output->format(\DateTime::RFC3339));
    }
}
