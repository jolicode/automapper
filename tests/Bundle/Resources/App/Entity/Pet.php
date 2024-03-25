<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Entity;

use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

#[
    DiscriminatorMap(typeProperty: 'type', mapping: [
        'cat' => Cat::class,
        'dog' => Dog::class,
    ])
]
class Pet
{
    /** @var string */
    public $type;
}
