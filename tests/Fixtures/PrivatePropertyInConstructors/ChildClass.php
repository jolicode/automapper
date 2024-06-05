<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\PrivatePropertyInConstructors;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ChildClass extends AbstractClass
{
    public function __construct(
        string $parentProp,
        private string $childProp,
    ) {
        parent::__construct($parentProp);
    }
}
