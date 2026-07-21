<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use Symfony\Component\Uid\AbstractUid;

/**
 * Transform a Symfony Uid to another Symfony Uid, keeping the concrete target class.
 *
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final readonly class SymfonyUidCopyTransformer implements TransformerInterface, CheckTypeInterface
{
    /**
     * @param class-string $targetClassName a Symfony Uid class (Uuid, Ulid or one of their subclasses)
     */
    public function __construct(
        private string $targetClassName,
    ) {
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        /*
         * Create the target Symfony Uid from another Symfony Uid. `fromString` is used so the concrete
         * target class is respected (e.g. UuidV4), and the base Uuid/Ulid class still returns the proper
         * versioned instance.
         *
         * \Symfony\Component\Uid\TargetUid::fromString((string) $input);
         */
        return [
            new Expr\StaticCall(new Name\FullyQualified($this->targetClassName), 'fromString', [
                new Arg(new Expr\Cast\String_($input)),
            ]),
            [],
        ];
    }

    public function getCheckExpression(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source): Expr
    {
        /* $input instanceof \Symfony\Component\Uid\AbstractUid */
        return new Expr\Instanceof_($input, new Name\FullyQualified(AbstractUid::class));
    }
}
