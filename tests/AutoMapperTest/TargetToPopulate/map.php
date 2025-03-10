<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\TargetToPopulate;

use AutoMapper\Tests\AutoMapperBuilder;

class VatModel
{
    /**
     * @var string
     */
    protected $countryCode;
    /**
     * @var string|null
     */
    protected $stateCode;
    /**
     * @var float
     */
    protected $standardVatRate;
    /**
     * @var float
     */
    protected $reducedVatRate;
    /**
     * @var bool
     */
    protected $displayIncVatPrices = false;

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getStateCode(): ?string
    {
        return $this->stateCode;
    }

    public function setStateCode(?string $stateCode): self
    {
        $this->stateCode = $stateCode;

        return $this;
    }

    public function getStandardVatRate(): float
    {
        return $this->standardVatRate;
    }

    public function setStandardVatRate(float $standardVatRate): self
    {
        $this->standardVatRate = $standardVatRate;

        return $this;
    }

    public function getReducedVatRate(): float
    {
        return $this->reducedVatRate;
    }

    public function setReducedVatRate(float $reducedVatRate): self
    {
        $this->reducedVatRate = $reducedVatRate;

        return $this;
    }

    public function getDisplayIncVatPrices(): bool
    {
        return $this->displayIncVatPrices;
    }

    public function setDisplayIncVatPrices(bool $displayIncVatPrices): self
    {
        $this->displayIncVatPrices = $displayIncVatPrices;

        return $this;
    }
}

class VatEntity
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var string|null
     */
    private $stateCode;

    /**
     * @var float
     */
    private $standardVatRate;

    /**
     * @var float
     */
    private $reducedVatRate;

    /**
     * @var bool
     */
    private $displayIncVatPrices;

    public function __construct(
        string $countryCode,
        ?string $stateCode = null,
        float $standardVatRate = 0,
        float $reducedVatRate = 0,
        bool $displayIncVatPrices = false,
    ) {
        $this->countryCode = $countryCode;
        $this->stateCode = $stateCode;
        $this->standardVatRate = $standardVatRate;
        $this->reducedVatRate = $reducedVatRate;
        $this->displayIncVatPrices = $displayIncVatPrices;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getStateCode(): ?string
    {
        return $this->stateCode;
    }

    public function setStateCode(?string $stateCode): void
    {
        $this->stateCode = $stateCode;
    }

    public function getStandardVatRate(): float
    {
        return $this->standardVatRate;
    }

    public function setStandardVatRate(float $standardVatRate): void
    {
        $this->standardVatRate = $standardVatRate;
    }

    public function getReducedVatRate(): float
    {
        return $this->reducedVatRate;
    }

    public function setReducedVatRate(float $reducedVatRate): void
    {
        $this->reducedVatRate = $reducedVatRate;
    }

    public function isDisplayIncVatPrices(): bool
    {
        return $this->displayIncVatPrices;
    }

    public function setDisplayIncVatPrices(bool $displayIncVatPrices): void
    {
        $this->displayIncVatPrices = $displayIncVatPrices;
    }
}

$source = new VatModel();
$source->setCountryCode('fr');
$source->setStandardVatRate(21.0);
$source->setReducedVatRate(5.5);
$source->setDisplayIncVatPrices(true);

$target = new VatEntity('en');
$target->setId(1);

return AutoMapperBuilder::buildAutoMapper()->map($source, $target);
