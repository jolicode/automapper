<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class PetOwnerWithConstructorArguments
{
    /** @var array<int, Pet> */
    private $pets;

    public function __construct(array $pets)
    {
        $this->pets = $pets;
    }

    /**
     * @return Pet[]
     */
    public function getPets(): array
    {
        return $this->pets;
    }

    public function addPet(Pet $pet): void
    {
        $this->pets[] = $pet;
    }

    public function removePet(Pet $pet): void
    {
        $index = array_search($pet, $this->pets);

        if ($index !== false) {
            unset($this->pets[$index]);
        }
    }
}
