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

namespace AutoMapper\Tests\Normalizer\Features;

class TypeEnforcementNumberObject
{
    /**
     * @var float
     */
    public $number;

    public function setNumber($number)
    {
        $this->number = $number;
    }
}
