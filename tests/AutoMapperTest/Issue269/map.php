<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\Issue269;

use AutoMapper\Tests\AutoMapperBuilder;

enum PaymentMethod: string
{
    case First = 'First';
}

class Dto
{
    /** @var PaymentMethod[] */
    public ?array $paymentMethods = null;
}

class Entity
{
    /** @var PaymentMethod[] */
    private array $paymentMethods = [];

    /** @param PaymentMethod[] $paymentMethods */
    public function setPaymentMethods(array $paymentMethods): self
    {
        $this->paymentMethods = $paymentMethods;

        return $this;
    }

    /** @return PaymentMethod[] */
    public function getPaymentMethods(): array
    {
        return $this->paymentMethods;
    }
}

$dto = new Dto();
$dto->paymentMethods = [PaymentMethod::First];

return AutoMapperBuilder::buildAutoMapper()->map($dto, Entity::class);
