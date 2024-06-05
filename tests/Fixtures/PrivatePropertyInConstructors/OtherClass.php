<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\PrivatePropertyInConstructors;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final readonly class OtherClass
{
    public function __construct(
        public string $parentProp,
        public string $childProp,
    ) {
    }
}
