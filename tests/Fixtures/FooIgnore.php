<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use Symfony\Component\Serializer\Annotation\Ignore;

class FooIgnore
{
    /**
     * @var int
     */
    #[Ignore]
    public $id;

    public function getId(): int
    {
        return $this->id;
    }
}
