# `#[MapTo]` and `#[MapFrom]` attributes

The `#[MapTo]` and `#[MapFrom]` attributes allow you to define the mapping between a property of the source and the target object.

Respectively, the `#[MapTo]` attribute is used on a property of the `source` object, and the `#[MapFrom]` attribute 
is used on a property of the `target` object.

They both allow the same arguments, but since you can map to or from a generic data structure, they may be needed 
depending on the context.

## Usage

They can be used on :

 * a public or private property (also in promoted properties)

```php
class Entity
{
    #[MapTo(property: 'name')]
    public string $title;
}
```

 * a public or private method

```
class EntityDto
{
    private string $name;

    #[MapFrom(property: 'title')]
    public function setName($name): void
    {
        $this->name = $name;
    }
}
```

 * a class (to add virtual properties)

```
#[MapTo(property: 'virtualProperty')]
class Entity {}
```

## Specifying the target or source

The `#[MapTo]` and `#[MapFrom]` attributes allow you to specify on which target or source this attribute should be applied.
You can use this attribute multiple times on the same property to handle behavior for different targets or sources.

```php
class Entity
{
    #[MapTo(target: EntityDto::class, property: 'name')]
    #[MapTo(target: 'array', property: 'title')]
    public string $title;
}
```



```php
class EntityDto
{
    #[MapFrom(source: Entity::class, property: 'title')]
    #[MapFrom(source: 'array', property: 'name')]
    public string $name;
}
```

You can also pass an array to the `target` or `source` argument to specify configuration for multiple targets or sources.
```php
class EntityDto
{
    #[MapFrom(source: Entity::class, property: 'title')]
    #[MapFrom(source: 'array', property: 'name')]
    public string $name;
    
    #[MapFrom(source: [Entity::class, 'array'], property: 'bar')]
    public string $foo;
}
```

In case there is multiple attributes that match the same target (not source), you can use the `priority` argument 
to specify which one should be used first. The default priority is `0`.

```php
class Entity
{
    #[MapTo(ignore: true)]
    public string $title;
}

class EntityDto
{
    #[MapFrom(source: Entity::class, ignore: false, priority: 10)]
    public string $title;
}
```

## DateTime format

You can override DateTime format for a property by using `#[MapTo]` or `#[MapFrom]` attributes:

```php
#[MapTo(dateTimeFormat: \DateTimeInterface::ATOM)]
#[MapFrom(dateTimeFormat: \DateTimeInterface::ATOM)]
```

This way, your DateTime property will be transformed to string with the corresponding format.
For more details about how each DateTime format configuration works together please read the dedicated page:
[DateTime format](./date-time.md).
