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

The `source` or `target` parameters can also be an array of classes, to override the mapping for multiple sources or targets
with a single attribute.

```php
#[Mapper(source: [EntityDto::class, AnotherEntityDto::class], constructorStrategy: ConstructorStrategy::NEVER)]
```

## Configuration

The `#[Mapper]` attribute supports most of the configuration parameters specified in the [global configuration](../getting-started/configuration.md).
It will override the global configuration for the specified mapping.

## Register

This attribute may also be used when registering mappers manually when using the Symfony bundle.

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
