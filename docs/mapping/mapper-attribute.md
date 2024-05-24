# `#[Mapper]` attribute

The `#[Mapper]` attribute allow you to configure how a mapper should be generated specifically.

```php
#[Mapper(constructorStrategy: ConstructorStrategy::NEVER)]
class Entity
{
    public string $foo;
}
```

In this example when a mapper is targeting the `Entity` class, the constructor will never be used.

## Specify a source or target

You can also limit the scope of the `#[Mapper]` attribute to a specific source or target.

```php
#[Mapper(source: EntityDto::class, constructorStrategy: ConstructorStrategy::NEVER)]
```

## DateTime format

You can override DateTime format for a whole class by using `#[Mapper]` attribute:

```php
#[Mapper(dateTimeFormat: \DateTimeInterface::ATOM)]
```

This way, all your class properties that are DateTime will be transformed to string with the corresponding format.
For more details about how each DateTime format configuration works together please read the dedicated page:
[DateTime format](./date-time.md).

## Priority

If multiple `#[Mapper]` attributes are defined for the same mapping, the one with the highest priority will be used.

```php
#[Mapper(source: EntityDto::class, constructorStrategy: ConstructorStrategy::NEVER, priority: 2)]
class Entity
{
    public string $foo;
}

#[Mapper(constructorStrategy: ConstructorStrategy::ALWAYS, priority: 1)]
class EntityDto
{
    public string $foo;
}
```

In this example when a mapper is targeting the `Entity` class from the `EntityDto` class the constructor will never be used
as the `Entity` mapper attribute has a higher priority.

If priorities are the same, order is not guaranteed.
