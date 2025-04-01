<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ConstructorWithSerializedName;

use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class Bar
{
    public function __construct(
        #[SerializedName('is_static')]
        public bool $isStatic
    ) {
    }
}

$autoMapper = AutoMapperBuilder::buildAutoMapper();


return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield 'to_array' => $autoMapper->map(new Bar(true), 'array');

    yield 'from_array' => $autoMapper->map(['is_static' => true], Bar::class);
})();
