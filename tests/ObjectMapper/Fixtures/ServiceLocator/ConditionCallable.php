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

namespace AutoMapper\Tests\ObjectMapper\Fixtures\ServiceLocator;

use Symfony\Component\ObjectMapper\ConditionCallableInterface;

/**
 * @implements ConditionCallableInterface<A>
 */
class ConditionCallable implements ConditionCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): bool
    {
        return 'ok' === $value;
    }
}
