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

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Test AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES.
 */
trait SkipUninitializedValuesTestTrait
{
    abstract protected function getNormalizerForSkipUninitializedValues(): NormalizerInterface;

    #[DataProvider('skipUninitializedValuesFlagProvider')]
    public function testSkipUninitializedValues(array $context)
    {
        self::markTestSkipped('Uninitialized properties are not supported yet');

        $object = new TypedPropertiesObjectWithGetters();

        $normalizer = $this->getNormalizerForSkipUninitializedValues();
        $result = $normalizer->normalize($object, null, $context);
        $this->assertSame(['initialized' => 'value'], $result);

        $normalizer->denormalize(
            ['unInitialized' => 'value'],
            TypedPropertiesObjectWithGetters::class,
            null,
            ['object_to_populate' => $objectToPopulate = new TypedPropertiesObjectWithGetters(), 'deep_object_to_populate' => true, 'skip_null_values' => true] + $context
        );

        $this->assertSame('value', $objectToPopulate->getUninitialized());
    }

    public static function skipUninitializedValuesFlagProvider(): iterable
    {
        yield 'passed manually' => [['skip_uninitialized_values' => true, 'groups' => ['foo']]];
        yield 'using default context value' => [['groups' => ['foo']]];
    }

    public function testWithoutSkipUninitializedValues()
    {
        $object = new TypedPropertiesObjectWithGetters();

        $normalizer = $this->getNormalizerForSkipUninitializedValues();

        try {
            $normalizer->normalize($object, null, ['skip_uninitialized_values' => false, 'groups' => ['foo']]);
            $this->fail('Normalizing an object with uninitialized property should have failed');
        } catch (\Error $e) {
            self::assertSame('Typed property AutoMapper\Tests\Normalizer\Features\TypedPropertiesObject::$unInitialized must not be accessed before initialization', $e->getMessage());
        }
    }
}
