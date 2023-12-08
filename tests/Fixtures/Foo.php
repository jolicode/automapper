<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use Symfony\Component\Serializer\Annotation\Groups;

class Foo
{
    /**
     * @var int
     */
    #[Groups(['group1', 'group2', 'group3'])]
    private $id = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
