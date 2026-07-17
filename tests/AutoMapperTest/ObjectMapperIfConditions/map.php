<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\ObjectMapperIfConditions;

use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\ObjectMapper\Attribute\Map;

class Source
{
    // if: false must never be mapped, like symfony/object-mapper does
    #[Map(if: false)]
    public string $secret = 'my secret';

    public string $name = 'john';

    // a string callable receives the property value only: boolval(false) => skipped
    #[Map(if: 'boolval')]
    public bool $active = false;

    // other callables receive ($value, $source, $target) like symfony/object-mapper
    #[Map(if: [self::class, 'shouldMapScore'])]
    public int $score = 42;

    public static function shouldMapScore(mixed $value, ?object $source = null, ?object $target = null): bool
    {
        return 42 === $value && $source instanceof self;
    }
}

class Target
{
    public string $secret = 'unchanged';
    public string $name = 'unchanged';
    public bool $active = true;
    public int $score = 0;
}

final class UppercaseCheck
{
    public function __invoke(mixed $value, ?object $source = null, ?object $target = null): bool
    {
        return \is_string($value) && $value === strtoupper($value);
    }
}

class SourceB
{
    public string $login = 'jdoe';
    public string $hidden = 'x';
    public string $code = 'ABC';

    public static function hasLogin(mixed $value, ?object $source = null, ?object $target = null): bool
    {
        return 'jdoe' === $value;
    }
}

class TargetB
{
    #[Map(source: 'login', if: [SourceB::class, 'hasLogin'])]
    public string $username = 'anonymous';

    #[Map(source: 'hidden', if: false)]
    public string $hidden = 'unchanged';

    #[Map(source: 'code', if: new UppercaseCheck())]
    public string $code = 'none';
}

// the class level Map target also matches child classes of the configured target
#[Map(target: BaseDto::class)]
class SourceC
{
    #[Map(target: 'fullName')]
    public string $name = 'zoe';
}

class BaseDto
{
    public string $fullName = '';
}

class ChildDto extends BaseDto
{
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    yield 'source-side' => $autoMapper->map(new Source(), Target::class);

    yield 'target-side' => $autoMapper->map(new SourceB(), TargetB::class);

    yield 'subclass-target' => $autoMapper->map(new SourceC(), ChildDto::class);
})();
