<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\DateTimeToStringTransformer;
use PHPUnit\Framework\TestCase;

class DateTimeToStringTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testDateTimeTransformer(): void
    {
        $transformer = new DateTimeToStringTransformer();

        $date = new \DateTime();
        $output = $this->evalTransformer($transformer, new \DateTime());

        self::assertSame($date->format(\DateTime::RFC3339), $output);
    }

    public function testDateTimeTransformerCustomFormat(): void
    {
        $transformer = new DateTimeToStringTransformer(\DateTime::COOKIE);

        $date = new \DateTime();
        $output = $this->evalTransformer($transformer, new \DateTime());

        self::assertSame($date->format(\DateTime::COOKIE), $output);
    }
}
