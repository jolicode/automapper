<?php

namespace AutoMapper\Tests\Fixtures;

use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

/**
 * @DiscriminatorMap(typeProperty="type", mapping={
 *    "cat"="AutoMapper\Tests\Fixtures\Cat",
 *    "dog"="AutoMapper\Tests\Fixtures\Dog",
 *    "fish"="AutoMapper\Tests\Fixtures\Fish"
 * })
 */
abstract class Pet
{
    /** @var string */
    public $type;

    /** @var string */
    public $name;

    /** @var PetOwner */
    public $owner;
}
