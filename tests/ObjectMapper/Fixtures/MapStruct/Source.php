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

namespace AutoMapper\Tests\ObjectMapper\Fixtures\MapStruct;

class Source
{
    public function __construct(
        public readonly string $propertyA,
        public readonly string $propertyB,
        public readonly string $propertyC,
    ) {
    }
}
