<?php

declare(strict_types=1);

namespace AutoMapper\Doctrine\Hydrator;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\AsciiStringType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;

/**
 * Generates the code of Type::convertToPHPValue() for a given DBAL type.
 *
 * Built-in types are inlined with the exact same semantics, the platform specific formats being resolved at
 * generation time. Any other type, or a built-in type overridden by the application, calls the type instance.
 *
 * @internal
 */
final class DbalTypeConverter
{
    /** @var array<string, true> */
    private array $usedTypes = [];

    public function __construct(
        private readonly AbstractPlatform $platform,
    ) {
    }

    /**
     * @return list<string> types that are called at runtime
     */
    public function getUsedTypes(): array
    {
        return array_keys($this->usedTypes);
    }

    /**
     * @param Expr\Variable $value evaluated several times, so it must be a variable
     */
    public function convert(Expr\Variable $value, ?string $typeName, Expr $hydrator): Expr
    {
        if (null === $typeName) {
            return $value;
        }

        $type = Type::getType($typeName);
        $null = new Expr\ConstFetch(new Name('null'));
        $isNull = new Expr\BinaryOp\Identical($null, $value);

        return match ($type::class) {
            IntegerType::class, SmallIntType::class => new Expr\Ternary($isNull, $null, new Expr\Cast\Int_($value)),
            FloatType::class => new Expr\Ternary($isNull, $null, new Expr\Cast\Double($value)),
            StringType::class, AsciiStringType::class, GuidType::class => $value,
            TextType::class => new Expr\Ternary(
                new Expr\FuncCall(new Name\FullyQualified('is_resource'), [new Arg($value)]),
                new Expr\FuncCall(new Name\FullyQualified('stream_get_contents'), [new Arg($value)]),
                $value,
            ),
            DecimalType::class => new Expr\Ternary(
                new Expr\BinaryOp\BooleanOr(
                    new Expr\FuncCall(new Name\FullyQualified('is_float'), [new Arg($value)]),
                    new Expr\FuncCall(new Name\FullyQualified('is_int'), [new Arg($value)]),
                ),
                new Expr\Cast\String_($value),
                $value,
            ),
            BooleanType::class => $this->hasDefaultBooleanConversion()
                ? new Expr\Ternary($isNull, $null, new Expr\Cast\Bool_($value))
                : $this->callType($value, $typeName, $hydrator),
            DateTimeImmutableType::class => $this->dateTime($value, \DateTimeImmutable::class, $this->platform->getDateTimeFormatString(), $typeName, $hydrator),
            DateTimeType::class => $this->dateTime($value, \DateTime::class, $this->platform->getDateTimeFormatString(), $typeName, $hydrator),
            DateImmutableType::class => $this->dateTime($value, \DateTimeImmutable::class, '!' . $this->platform->getDateFormatString(), $typeName, $hydrator),
            default => $this->callType($value, $typeName, $hydrator),
        };
    }

    /**
     * null or already converted values are kept, the format of the platform is tried first, then the type itself
     * handles the other formats and the errors.
     *
     * @param class-string<\DateTimeInterface> $class
     */
    private function dateTime(Expr\Variable $value, string $class, string $format, string $typeName, Expr $hydrator): Expr
    {
        return new Expr\Ternary(
            new Expr\BinaryOp\BooleanOr(
                new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $value),
                new Expr\Instanceof_($value, new Name\FullyQualified($class)),
            ),
            $value,
            new Expr\Ternary(
                new Expr\StaticCall(new Name\FullyQualified($class), 'createFromFormat', [new Arg(new Scalar\String_($format)), new Arg($value)]),
                null,
                $this->callType($value, $typeName, $hydrator),
            ),
        );
    }

    private function callType(Expr\Variable $value, string $typeName, Expr $hydrator): Expr
    {
        $this->usedTypes[$typeName] = true;

        return new Expr\MethodCall(
            new Expr\ArrayDimFetch(new Expr\PropertyFetch($hydrator, 'types'), new Scalar\String_($typeName)),
            'convertToPHPValue',
            [new Arg($value), new Arg(new Expr\PropertyFetch($hydrator, 'platform'))],
        );
    }

    private function hasDefaultBooleanConversion(): bool
    {
        return AbstractPlatform::class === (new \ReflectionMethod($this->platform, 'convertFromBoolean'))->getDeclaringClass()->name;
    }
}
