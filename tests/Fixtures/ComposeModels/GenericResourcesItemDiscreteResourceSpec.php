<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class GenericResourcesItemDiscreteResourceSpec
{
    public function __construct(public string|null $kind = NULL, public float|null $value = NULL)
    {
    }
}