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

use AutoMapper\MapperContext;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Test AbstractObjectNormalizer::SKIP_NULL_VALUES.
 */
trait SkipNullValuesTestTrait
{
    abstract protected function getNormalizerForSkipNullValues(): NormalizerInterface;

    public function testSkipNullValues()
    {
        $dummy = new ObjectDummy();
        $dummy->bar = 'present';

        $normalizer = $this->getNormalizerForSkipNullValues();
        $result = $normalizer->normalize($dummy, null, [MapperContext::SKIP_NULL_VALUES => true]);
        $this->assertSame(['bar' => 'present', 'fooBar' => 'present'], $result);
    }
}
