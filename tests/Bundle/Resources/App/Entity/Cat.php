<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Entity;

use AutoMapper\Attribute\MapProvider;
use AutoMapper\Tests\Bundle\Resources\App\Service\CatProvider;

#[MapProvider(CatProvider::class)]
class Cat extends Pet
{
}
