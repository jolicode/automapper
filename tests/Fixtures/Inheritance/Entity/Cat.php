<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Inheritance\Entity;

class Cat extends Animal
{
    /** @var int<0,10> */
    public int $meowLoudness = 5;
}
