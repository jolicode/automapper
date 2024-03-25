# Mapping Context

When using the `map` method, you can pass a `context` as the third argument. This `context` can be used to change 
the behavior of the mapping process.

```php
$source = new User();
$target = $autoMapper->map($source, UserDTO::class, ['key' => 'value']);
```

### Groups

Groups allow you to define a subset of properties that should be mapped.

```php
$source = new User();
$target = $autoMapper->map($source, UserDTO::class, ['groups' => ['read']]);
```

In this case only properties that have been flagged with the `read` group will be mapped.

> [!WARNING]
> Groups are checked on both the source and target objects, both must have one of the groups from the context to be mapped.


### Allowed attributes

Allowed attributes allow you to define a subset of properties that should be mapped. This is
the same as groups but it does need to be defined on the source or target object.

```php
$source = new User();
$target = $autoMapper->map($source, UserDTO::class, [
    'allowed_attributes' => ['id']
]);
```

In this case only the `id` property will be mapped.

> [!WARNING]
> The mapper will only check the allowed attributes from the property name of the source object
> and not the target object.

You can also provide a nested array to allow only specific attributes of nested objects.

```php
$source = new User();
$source->address = new Address();
$target = $autoMapper->map($source, UserDTO::class, [
    'allowed_attributes' => ['address' => ['city']]
]);
```

In this case only the `city` property of the `address` object will be mapped.

### Ignored attributes

Ignored attributes is the opposite of allowed attributes, it allows you to define a subset of properties 
that should not be mapped.

```php
$source = new User();
$target = $autoMapper->map($source, UserDTO::class, [
    'ignored_attributes' => ['id']
]);
```

In this case all properties except the `id` property will be mapped.

### Circular references

You may have circular references in your objects i.e. an object references itself.
AutoMapper by default will keep track of the objects it has already mapped to avoid infinite loops and conserve circular
references in the target object.

```php
$source = new User();
$source->friend = $source;
$target = $autoMapper->map($source, UserDTO::class);

assert($target->friend === $target); // true
```

Sometimes you may want to break the circular reference, you can do this by passing the `circular_reference_limit` option.

```php
$source = new User();
$source->friend = $source;
$target = $autoMapper->map($source, UserDTO::class, ['circular_reference_limit' => 0]);
```

In this case a `CircularReferenceException` will be thrown.

You can also specify a callback to handle the circular reference.

```php
$source = new User();
$source->friend = $source;
$target = $autoMapper->map($source, UserDTO::class, [
    'circular_reference_handler' => function ($source, $context) {
        return null;
    }
]);
```

In this case the `friend` property will be set to `null`.

### Skip null values

In some case having a null value may precise that the value should not be mapped and the target should keep its value.

You can enable this behavior by passing the `skip_null_values` option.

```php
$source = new User();
$source->name = null;
$target = new UserDTO();
$target->name = 'Jane';
$target = $autoMapper->map($source, $target, ['skip_null_values' => true]);

assert($target->name === 'Jane'); // true
```

### Date Time format

When mapping a `DateTimeInterface` object to a string, AutoMapper will format the date, you can change the format by
passing the `date_time_format` option.

```php
$source = new User();
$source->createdAt = new DateTime('2021-01-01');
$target = $autoMapper->map($source, UserDTO::class, ['date_time_format' => 'Y-m-d']);
```

In this case the `createdAt` property will be mapped to the string `2021-01-01`.

By default AutoMapper uses the `DateTimeInterface::RFC3339` format.

### Map to accessor parameter

When mapping from an object, AutoMapper will use the best available method or property to fetch the value of the property.
Sometimes this method may have parameters, you can specify those parameters by passing the `map_to_accessor_parameter` option.

You will also have to link the parameter of the method to the context by using the `MapToContext` attribute.

```php
class User {
    public function __construct(private string $name) {}
    
    public function getName(#[MapToContext('suffix')] string $suffix): string {
        return $this->name . $suffix;
    }
}

$source = new User(name: 'Jane');
$target = $autoMapper->map($source, UserDTO::class, [
    'map_to_accessor_parameter' => ['suffix' => ' Doe']
]);
```

### Constructor arguments

When mapping to an object, AutoMapper will try to use the constructor to instantiate the object. Sometimes some parameters
may not be available in the source object, you can specify those parameters by passing the `constructor_arguments` option.

```php
class UserDto {
    public function __construct(private string $name, private \DateTime $createdAt) {}
}

$source = new User();
$source->name = 'Jane';

$target = $autoMapper->map($source, UserDTO::class, [
    'constructor_arguments' => [
        UserDTO::class => ['createdAt' => new \DateTime('2021-01-01')]
    ]
]);
```

