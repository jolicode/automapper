# Property Type

When mapping properties, AutoMapper uses the property type to determine how to map the value from the source to the target. 

It works well when both source and target properties are object, but when mapping to or from a generic data structure 
like an array or `\stdClass`, the property type is not available.

In this case the type is, by default, transformed to a native PHP type (`int`, `float`, `string`, `bool`, `array`, `object`, `null`).

You can override this behavior by specifying the `sourcePropertyType` or `targetPropertyType` argument in the `#[MapTo]` or 
`#[MapFrom]` attributes. You can override the source type, the target type, or both, depending on the mapping direction.

```php
class Entity
{
    #[MapTo(target: 'array', targetPropertyType: 'int')]
    public string $number;
}
```

In this example, when mapping to an array, the `number` property will be converted to an `int` instead of a `string`.

This can also be useful when mapping to an object type with an union type, but you want to force a specific type during the mapping.

```php
class EntityDto
{
    #[MapTo(target: Entity::class, sourcePropertyType: 'int')]
    private int|float $value;
}
```

In this example we consider, that the source property is always an `int`, so AutoMapper will never consider 
the `float` type during the mapping.
