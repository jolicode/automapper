# Understanding the `source` and `target`

The `source` and `target` are the most important concepts in AutoMapper.

 - The `source` is the object or data that you want to map from.
 - The `target` is the object or data that you want to map to.

### Mapper: Object to Object

A Mapper happens when the `source` and `target` are both user defined class.

```php
$source = new User();
$target = $autoMapper->map($source, UserDTO::class);
```

There may be case when you want to map to an existing object.

```php
$source = new User();
$target = new UserDTO();
$autoMapper->map($source, $target);
```

In the case the mapper will update the `target` object with the values from the `source` object.

### Normalization : Object to Array

Normalization is the process of converting an `object` to an `array` with scalar values.

```php
$source = new User();
$target = $autoMapper->map($source, 'array');
```

You can also normalize to an `stdClass`.

```php
$source = new User();
$target = $autoMapper->map($source, \stdClass::class);
```

Like the `map` method, you can also normalize to an existing array.

```php
$source = new User();
$target = ['id' => 1];
$target = $autoMapper->map($source, $target);
```

> [!NOTE]
> In this case you have to assign the result of the `map` method to the `$target` variable since it is not passed by reference.


### Denormalization : Array to Object

Denormalization is the process of converting an `array` or `stdClass` to an object.

```php
$source = ['id' => 1];
$target = $autoMapper->map($source, User::class);
```

### Cloning

Clone is a special case of mapping where the `source` and `target` are the same class.

```php
$source = new User();
$target = $autoMapper->map($source, User::class);

assert($source !== $target);
```

> [!NOTE]
> Also in this case it will do a deep clone of the `source` object, even sub-objects will be cloned and not referenced.

