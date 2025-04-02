<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\DiscriminatorMapAndInterface;

class Something
{
    public function __construct(
        public MyInterface $myInterface,
    ) {
    }
}
