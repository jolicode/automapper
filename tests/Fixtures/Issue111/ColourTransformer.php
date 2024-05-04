<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Issue111;

use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class ColourTransformer implements PropertyTransformerInterface
{
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        $colours = [];

        foreach ($value as $colour) {
            $colours[] = new Colour($colour);
        }

        return $colours;
    }
}
