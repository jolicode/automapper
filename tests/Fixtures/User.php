<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

use AutoMapper\Attribute\MapTo;

class User
{
    /**
     * @var int
     */
    #[MapTo(target: 'array', property: '_id')]
    private $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string|int
     */
    public $age;

    /**
     * @var string
     */
    private $email;

    /**
     * @var Address
     */
    public $address;

    /**
     * @var Address[]
     */
    public $addresses = [];

    /**
     * @var \DateTime|null
     */
    public $createdAt;

    /**
     * @var float
     */
    public $money;

    /**
     * @var iterable
     */
    public $languages;

    /**
     * @var mixed[]
     */
    protected $properties = [];

    public function __construct($id, $name, $age)
    {
        $this->id = $id;
        $this->name = $name;
        $this->age = $age;
        $this->email = 'test';
        $this->createdAt = new \DateTime();
        $this->money = 20.10;
        $this->languages = new \ArrayObject();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getProperties(): iterable
    {
        return $this->properties;
    }

    public function setProperties(iterable $properties): void
    {
        $this->properties = $properties;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }
}
