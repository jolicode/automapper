<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\PrivatePropertyInConstructors;

use AutoMapper\Tests\AutoMapperBuilder;

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

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield 'from-array' => $autoMapper->map(
        [
            'parentProp' => 'foo',
            'childProp' => 'bar',
        ],
        ChildClass::class
    );

    yield 'from-class' => $autoMapper->map(
        new OtherClass(parentProp: 'foo', childProp: 'bar'),
        ChildClass::class
    );
})();
