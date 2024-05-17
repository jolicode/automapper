<?php

/**
 * This file provides compatibility functions for nikic/php-parser v4 & v5.
 */

declare(strict_types=1);

namespace AutoMapper\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;

/**
 * Constructs an integer number scalar node.
 *
 * @param int                  $value      Value of the number
 * @param array<string, mixed> $attributes Additional attributes
 *
 * @internal
 */
function create_scalar_int(int $value, array $attributes = []): Scalar\Int_|Scalar\LNumber
{
    $class = class_exists(Scalar\Int_::class) ? Scalar\Int_::class : Scalar\LNumber::class;

    return new $class($value, $attributes);
}

/**
 * Constructs an array item node.
 *
 * @param Expr                 $value      Value
 * @param Expr|null            $key        Key
 * @param bool                 $byRef      Whether to assign by reference
 * @param array<string, mixed> $attributes Additional attributes
 *
 * @internal
 */
function create_expr_array_item(Expr $value, Expr $key = null, bool $byRef = false, array $attributes = [], bool $unpack = false): Node\ArrayItem|Expr\ArrayItem
{
    $class = class_exists(Node\ArrayItem::class) ? Node\ArrayItem::class : Expr\ArrayItem::class;

    return new $class($value, $key, $byRef, $attributes, $unpack);
}
