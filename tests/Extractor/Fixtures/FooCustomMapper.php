<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Extractor\Fixtures;

class FooCustomMapper
{
    public function transform(mixed $object): mixed
    {
        if ($object instanceof Foo) {
            $object->bar = 'Hello World!';
        }

        return $object;
    }

    public function switch(mixed $object, string $someString): mixed
    {
        if ($object instanceof Foo) {
            $object->bar = 'Hello World!';
            $object->baz = $someString;
        }

        return $object;
    }
}
