<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AutoMapper\Tests\Normalizer\Features;

use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Test AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT and AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER.
 */
trait CircularReferenceTestTrait
{
    abstract protected function getNormalizerForCircularReference(): NormalizerInterface;

    abstract protected function getSelfReferencingModel();

    public function provideUnableToNormalizeCircularReference(): array
    {
        return [
            [[], 1],
            [['circular_reference_limit' => 3], 3],
        ];
    }

    /**
     * @dataProvider provideUnableToNormalizeCircularReference
     */
    public function testUnableToNormalizeCircularReference(array $context, int $expectedLimit)
    {
        $normalizer = $this->getNormalizerForCircularReference();

        $obj = $this->getSelfReferencingModel();

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage(sprintf('A circular reference has been detected when mapping the object of type "%s" (configured limit: %d).', $obj::class, $expectedLimit));
        $normalizer->normalize($obj, null, $context);
    }

    public function testCircularReferenceHandler()
    {
        $normalizer = $this->getNormalizerForCircularReference();

        $obj = $this->getSelfReferencingModel();
        $expected = ['me' => $obj::class];

        $context = [
            'circular_reference_handler' => function ($actualObj, string $format, array $context) use ($obj) {
                $this->assertInstanceOf($obj::class, $actualObj);
                $this->assertSame('test', $format);
                $this->assertArrayHasKey('foo', $context);

                return $actualObj::class;
            },
            'foo' => 'bar',
        ];
        $this->assertEquals($expected, $normalizer->normalize($obj, 'test', $context));
    }
}
