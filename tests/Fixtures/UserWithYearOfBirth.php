<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use Symfony\Component\Serializer\Annotation\Groups;

class UserWithYearOfBirth
{
    /**
     * @var int
     */
    #[Groups('read')]
    private $id;

    /**
     * @var string
     */
    #[Groups('read')]
    public $name;

    /**
     * @var string|int
     */
    #[Groups('read')]
    public $age;

    public function __construct($id, $name, $age)
    {
        $this->id = $id;
        $this->name = $name;
        $this->age = $age;
    }

    #[Groups('read')]
    public function getYearOfBirth()
    {
        return ((int) date('Y')) - ((int) $this->age);
    }
}
