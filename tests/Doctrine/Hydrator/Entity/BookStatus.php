<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Doctrine\Hydrator\Entity;

enum BookStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
