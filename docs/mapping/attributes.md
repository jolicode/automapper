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
    #[MapTo(name: 'name')]
    public string $title;
}
```

 * a public or private method

```
class EntityDto
{
    private string $name;

    #[MapFrom(name: 'title')]
    public function setName($name): void
    {
        $this->name = $name;
    }
}
```

 * a class (to add virtual properties)

```
#[MapTo(name: 'virtualProperty')]
class Entity {}
```

## Specifying the target or source

The `#[MapTo]` and `#[MapFrom]` attributes allow you to specify on which target or source this attribute should be applied.
You can use this attribute multiple times on the same property to handle behavior for different targets or sources.

```php
class Entity
{
    #[MapTo(target: EntityDto::class, name: 'name')]
    #[MapTo(target: 'array', name: 'title')]
    public string $title;
}
```



```php
class EntityDto
{
    #[MapFrom(source: Entity::class, name: 'title')]
    #[MapFrom(source: 'array', name: 'name')]
    public string $name;
}
```

> [!WARNING]
> If multiple `#[MapTo]` and/or `#[MapFrom]` attributes target the same property an exception will be thrown.
