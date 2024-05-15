<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\PrivatePropertyInConstructors;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
abstract class AbstractClass
{
    public function __construct(
        private string $parentProp,
    ) {
    }
}
