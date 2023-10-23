<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Transformer\StringToDateTimeTransformer;
use PHPUnit\Framework\TestCase;

class StringToDateTimeTransformerTest extends TestCase
{
    use EvalTransformerTrait;

    public function testDateTimeTransformer(): void
    {
        $transformer = new StringToDateTimeTransformer(\DateTime::class);

        $date = new \DateTime();
        $output = $this->evalTransformer($transformer, $date->format(\DateTime::RFC3339));

        self::assertInstanceOf(\DateTime::class, $output);
        self::assertSame($date->format(\DateTime::RFC3339), $output->format(\DateTime::RFC3339));
    }

    public function testDateTimeTransformerCustomFormat(): void
    {
        $transformer = new StringToDateTimeTransformer(\DateTime::class, \DateTime::COOKIE);

        $date = new \DateTime();
        $output = $this->evalTransformer($transformer, $date->format(\DateTime::COOKIE));

        self::assertInstanceOf(\DateTime::class, $output);
        self::assertSame($date->format(\DateTime::RFC3339), $output->format(\DateTime::RFC3339));
    }

    public function testDateTimeTransformerImmutable(): void
    {
        $transformer = new StringToDateTimeTransformer(\DateTimeImmutable::class, \DateTime::COOKIE);

        $date = new \DateTime();
        $output = $this->evalTransformer($transformer, $date->format(\DateTime::COOKIE));

        self::assertInstanceOf(\DateTimeImmutable::class, $output);
    }

    public function testDateTimeTransformerInterface(): void
    {
        $transformer = new StringToDateTimeTransformer(\DateTimeInterface::class);

        $date = new \DateTime();
        $output = $this->evalTransformer($transformer, $date->format(\DateTime::RFC3339));

        self::assertInstanceOf(\DateTimeImmutable::class, $output);
    }
}
