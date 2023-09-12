<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures;

class Node
{
    /**
     * @var Node
     */
    public $parent;

    /**
     * @var Node[]
     */
    public $childs = [];
}
