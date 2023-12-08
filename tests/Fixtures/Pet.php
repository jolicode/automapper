<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'cat' => Cat::class,
    'dog' => Dog::class,
    'fish' => Fish::class,
])]
abstract class Pet
{
    /** @var string */
    public $type;

    /** @var string */
    public $name;

    /** @var PetOwner */
    public $owner;
}
