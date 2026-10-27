<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator\Plan;

/**
 * How the concrete class of an alias is resolved for each row.
 *
 * @internal
 */
final readonly class DiscriminatorPlan
{
    public function __construct(
        /** Column of the result set holding the discriminator */
        public string $column,
        /** DBAL type of the column, only applied by the ObjectHydrator */
        public ?string $type,
        /** @var class-string<\BackedEnum>|null */
        public ?string $enumType,
        /** @var array<string, int> discriminator value => class index */
        public array $map,
        /** Name used in the exceptions: the discriminator column (object mode) or its result column (simple mode) */
        public string $name,
    ) {
    }
}
