<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AutoMapper\Tests\ObjectMapper\Fixtures\InstanceCallbackWithArguments;

class B
{
    public mixed $transformValue;
    public object $transformSource;

    public static function newInstance(mixed $value, object $source): self
    {
        $b = new self();
        $b->transformValue = $value;
        $b->transformSource = $source;

        return $b;
    }
}
