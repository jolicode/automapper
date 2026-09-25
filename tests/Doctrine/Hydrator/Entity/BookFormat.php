<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

enum BookFormat: int
{
    case Paperback = 1;
    case Hardcover = 2;
}
