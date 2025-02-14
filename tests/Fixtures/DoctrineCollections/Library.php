<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\DoctrineCollections;

use Doctrine\Common\Collections\Collection;

class Library
{
    /** @var Collection<Book> */
    public Collection $books;
}
