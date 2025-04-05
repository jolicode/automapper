<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Covariance;

use AutoMapper\Attribute\MapTo;
use AutoMapper\Tests\AutoMapperBuilder;

class GenericB
{
}

class GenericA
{
    /**
     * @var GenericB
     */
    #[MapTo(extractTypesFromGetter: true)]
    protected $b;

    public function getB(): GenericB
    {
        return $this->b;
    }

    public function setB(GenericB $b): void
    {
        $this->b = $b;
    }
}

class ExtendedB extends GenericB
{
    public function specificToB(): string
    {
        return 'result from ExtendedB';
    }
}

class ExtendedA extends GenericA
{
    /**
     * @var ExtendedB
     */
    protected $b;

    public function getB(): ExtendedB
    {
        return $this->b;
    }
}

$autoMapper = AutoMapperBuilder::buildAutoMapper(mapPrivatePropertiesAndMethod: true);

$genericA = new GenericA();
$genericB = new GenericB();
$genericA->setB($genericB);

return $autoMapper->map($genericA, ExtendedA::class);
