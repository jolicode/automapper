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

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Test type mismatches with a denormalizer that is aware of types.
 * Covers AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT.
 */
trait TypeEnforcementTestTrait
{
    abstract protected function getDenormalizerForTypeEnforcement(): DenormalizerInterface;

    public function testRejectInvalidType()
    {
        $denormalizer = $this->getDenormalizerForTypeEnforcement();

        $this->expectException(\TypeError::class);
        $denormalizer->denormalize(['date' => 'foo'], ObjectOuter::class);
    }

    public function testDoNotRejectInvalidTypeOnDisableTypeEnforcementContextOption()
    {
        $denormalizer = $this->getDenormalizerForTypeEnforcement();

        $this->assertSame('foo', $denormalizer->denormalize(
            ['number' => 'foo'],
            TypeEnforcementNumberObject::class,
            null,
            [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
        )->number);
    }
}
