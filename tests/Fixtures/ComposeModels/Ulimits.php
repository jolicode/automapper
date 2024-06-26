<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Ulimits
{
    public function __construct(public int|null $hard = NULL, public int|null $soft = NULL)
    {
    }
}