# Conditional Mapping

Ignoring a property is a good way to exclude it from the mapping process, but sometimes you may want to map a property conditionally.
This can be done using the `#[MapTo]` or `#[MapFrom]` attributes with the `if` argument.

The argument may accept several types of values:

### Expression language

You can use the Symfony Expression Language to define the condition.
In this context the `source` object is available as `source` and the `context` array is available as `context`.

```php
class Source
{
    public bool $propertyIsValid = true;

    #[MapTo(if: 'source.propertyIsValid and (context["custom_key"] ?? false) == true')]
    public $property;
}
```

If you use the Bundle version of the AutoMapper, there is also [additional functions available](../bundle/expression-language.md).

> [!NOTE]
> In standalone mode we do not provide any functions to the expression language.
> However we are interested in adding some functions to the expression language in the future. If you have some use
> cases that you would like to see covered, please open an issue on the GitHub repository.

### PHP function

You can use a php function to define the condition. This function must return a boolean value.

```php
class Source
{
    #[MapTo(if: 'boolval')]
    public string $property = '';
}
```

> [!WARNING]
> If the PHP function need more arguments than the `source` object and the `context` array, it will throw an exception.

### Static callback

You can use a static callback to define the condition.

```php
class Source
{
    public bool $propertyIsValid = true;

    #[MapTo(if: [self::class, 'isPropertyValid'])]
    public $property;
    
    public static function isPropertyValid(Source $source, array $context): bool
    {
        return $source->propertyIsValid && ($context['custom_key'] ?? false) === true;
    }
}
```

The static callback can accept the `source` object and the `context` array as arguments.

### Dynamic callback

You can also reference a method of the object declaring the attribute to define the condition.

```php
class Source
{
    public bool $propertyIsValid = true;

    #[MapTo(if: 'isPropertyValid')]
    public $property;
    
    public function isPropertyValid(): bool
    {
        return $this->propertyIsValid;
    }
}
```

The dynamic callback can accept the `source` object and the `context` array as arguments.
