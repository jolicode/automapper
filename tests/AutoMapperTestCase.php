<?php

declare(strict_types=1);

namespace AutoMapper\Tests;

use AutoMapper\AutoMapper;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
abstract class AutoMapperTestCase extends TestCase
{
    protected AutoMapper $autoMapper;

    protected function setUp(): void
    {
        $this->autoMapper = AutoMapperBuilder::buildAutoMapper();
    }

    protected function tearDown(): void
    {
        unset($this->autoMapper);
    }
}
