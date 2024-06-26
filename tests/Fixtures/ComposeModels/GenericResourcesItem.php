<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class GenericResourcesItem
{
    public function __construct(public GenericResourcesItemDiscreteResourceSpec|null $discreteResourceSpec = NULL)
    {
    }
}