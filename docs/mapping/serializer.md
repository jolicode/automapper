# Symfony Serializer Attributes

Symfony Serializer is a powerful component that can serialize and deserialize objects to and from various formats.
It can use several attributes to customize the serialization process.

When this component is available, AutoMapper can use these attributes to customize the mapping process.

### `#[Groups]`

The Symfony Serializer `#[Groups]` attribute can be used to define groups of properties that should be mapped.

```php
use Symfony\Component\Serializer\Attribute\Groups;

class Source
{
    #[Groups(['group1', 'group2'])]
    public $groupedProperty;
}
```

>! [!WARNING]
> When both `target` and `source` objects have groups, the property will be mapped only if the context contains at least
> one group from the `target` object and one group from the `source` object.

[More information on the Groups attribute](https://symfony.com/doc/current/components/serializer.html#attributes-groups)

### `#[Ignore]`

The Symfony Serializer `#[Ignore]` attribute can be used to ignore a property during the mapping process.

```php
use Symfony\Component\Serializer\Attribute\Ignore;

class Source
{
    #[Ignore]
    public $ignoredProperty;
}
```

[More information on the Ignore attribute](https://symfony.com/doc/current/components/serializer.html#ignoring-attributes)

### `#[MaxDepth]`

The Symfony Serializer `#[MaxDepth]` attribute can be used to limit the depth of the serialization process.

```php
use Symfony\Component\Serializer\Attribute\MaxDepth;

class Source
{
    #[MaxDepth(1)]
    public  $nestedProperty;
}
```

[More information on the MaxDepth attribute](https://symfony.com/doc/current/components/serializer.html#handling-serialization-depth)

### Name converters

AutoMapper can use the Symfony Serializer name converters to convert the property names, when mapping to 
or from an array.

```php
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

$autoMapper = AutoMapper::create(nameConverter: new CamelCaseToSnakeCaseNameConverter());
```

It also supports the `Symfony\Component\Serializer\Attribute\SerializedName` attribute.

```php
use Symfony\Component\Serializer\Attribute\SerializedName;

class Source
{
    #[SerializedName('nested')]
    public $nestedProperty;
}
```

[More information on the Name converters](https://symfony.com/doc/current/components/serializer.html#converting-property-names-when-serializing-and-deserializing)

### Normalizer Bridge

Additionally, this library provide a normalizer which implements the `Symfony\Component\Serializer\Normalizer\NormalizerInterface`
interface.

It's goal is to be as close as possible to the `ObjectNormalizer` of the `symfony/serializer` component, but with a focus on
performance.

```php
use AutoMapper\Normalizer\AutoMapperNormalizer;
use Symfony\Component\Serializer\Serializer;

$autoMapper = AutoMapper::create();
$serializer = new Serializer([new AutoMapperNormalizer($autoMapper)]);
```
