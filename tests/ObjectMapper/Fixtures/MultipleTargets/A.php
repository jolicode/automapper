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

namespace AutoMapper\Tests\ObjectMapper\Fixtures\MultipleTargets;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: B::class, if: [A::class, 'shouldMapToB'])]
#[Map(target: C::class, if: [A::class, 'shouldMapToC'])]
class A
{
    public function __construct(
        public readonly string $foo = 'bar',
    ) {
    }

    public static function shouldMapToB(mixed $value, object $object): bool
    {
        return false;
    }

    public static function shouldMapToC(mixed $value, object $object): bool
    {
        return true;
    }
}
