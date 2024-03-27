<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Normalizer\Features;

class CircularReferenceDummy
{
    /** @return self */
    public function getMe()
    {
        return $this;
    }
}
