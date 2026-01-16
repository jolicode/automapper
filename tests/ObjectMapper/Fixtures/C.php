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

namespace AutoMapper\Tests\ObjectMapper\Fixtures;

use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(D::class)]
class C
{
    public function __construct(
        #[Map('baz')] public readonly string $foo,
        #[Map('bat')] public readonly string $bar,
    ) {
    }
}
