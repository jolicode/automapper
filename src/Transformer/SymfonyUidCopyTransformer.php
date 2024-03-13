<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * Transform Symfony Uid to the same object.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class SymfonyUidCopyTransformer implements TransformerInterface
{
    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, /* Expr\Variable $source */): array
    {
        if (\func_num_args() < 5) {
            trigger_deprecation('jolicode/automapper', '8.2', 'The "%s()" method will have a new "Expr\Variable $source" argument in version 9.0, not defining it is deprecated.', __METHOD__);
        }

        /*
         * Create a Symfony Uid object from another Symfony Uid object.
         *
         * $input instanceof \Symfony\Component\Uid\Ulid ? new \Symfony\Component\Uid\Ulid($input->toBase32()) : new \Symfony\Component\Uid\Uuid($input->toRfc4122());
         */
        return [
            new Expr\Ternary(
                new Expr\Instanceof_($input, new Name(Ulid::class)),
                new Expr\New_(new Name(Ulid::class), [new Arg(new Expr\MethodCall($input, 'toBase32'))]),
                new Expr\New_(new Name(Uuid::class), [new Arg(new Expr\MethodCall($input, 'toRfc4122'))])
            ),
            [],
        ];
    }
}
