# Transformer

Transformers are the way the AutoMapper transforms the value from a `source` property to a `target` property.

By default, it tries to find the best transformer given the type of the `source` and `target` properties.

In some cases you may want to use a custom transformer. This can be done by using the `transformer` parameter of the
`#[MapTo]` or `#[MapFrom]` attributes.

Like the `if` argument, the `transformer` argument can accept several types of values.

### Expression language

You can use the Symfony Expression Language to define the transformer.
In this context the `source` object is available as `source` and the `context` array is available as `context`.

```php
class Source
{
    #[MapTo(transformer: "source.property === 'foo' ? 'bar' : 'baz'")]
    public string $property;
}
```

If you use the Bundle version of the AutoMapper, there is also [additional functions available](../bundle/expression-language.md).

> [!NOTE]
> In standalone mode we do not provide any functions to the expression language.
> However we are interested in adding some functions to the expression language in the future. If you have some use
> cases that you would like to see covered, please open an issue on the GitHub repository.

### Using a callable

```php
class Source
{
    #[MapTo(transformer: 'strtoupper')]
    public string $property;
}
```

In this case it will use the `strtoupper` PHP function to transform the value of the `property` property.

### Using a static callback

```php
class Source
{
    #[MapTo(transformer: [self::class, 'transform'])]
    public string $property;

    public static function transform(string $value, Source $source, array $context): string
    {
        return strtoupper($value);
    }
}
```

The callback will receive, the value of the source property, the whole `source` object and the `context` array.

### Creating a custom transformer

> [!WARNING]
> Custom transformers are experimental and may change in the future.

You can also create a custom transformer by implementing the `PropertyTransformerInterface` interface.
This can be useful if you need external dependencies or if you want to reuse the transformer in multiple places.

```php
namespace App\Transformer;

use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;

class UrlTransformer implements PropertyTransformerInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        return $this->urlGenerator->generate('my_route', ['id' => $value]);
    }
}
```

You will need to register the transformer in the `AutoMapper` instance.

```php
use AutoMapper\AutoMapper;

$autoMapper = AutoMapper::create(propertyTransformers: [new UrlTransformer($urlGenerator)]);
```

Then you can use it in the `transformer` argument.

```php
use App\Transformer\UrlTransformer;

class Source
{
    #[MapTo(name: 'url', transformer: UrlTransformer::class)]
    public int $id;
}
```

### Automatically apply custom transformers

You may want to automatically apply a custom transformer given a specific condition.
In order to do so, you can implement the `PropertyTransformerSupportInterface` interface.

```php
namespace App\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerInterface;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

class UrlTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    
    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        $sourceUniqueType = $types->getSourceUniqueType();

        if (null === $sourceUniqueType) {
            return false;
        }

        return $sourceUniqueType->getBuiltinType() === 'int' && $source->name === 'id' && $target->name === 'url';
    }
    
    public function transform(mixed $value, object|array $source, array $context): mixed
    {
        return $this->urlGenerator->generate('my_route', ['id' => $value]);
    }
}
```

In this case every transformation where the source `id` property is an `int` and the target property is named `url` 
will use the `UrlTransformer`. There is no need to specify the transformer in the `#[MapTo]` or `#[MapFrom]` attribute.

### Prioritize transformers

If you have multiple transformers that can be applied to the same transformation, you can prioritize them by using the
`PrioritizedPropertyTransformerInterface` interface.

```php
namespace App\Transformer;

class UrlTransformer implements PropertyTransformerInterface, PropertyTransformerSupportInterface, PrioritizedPropertyTransformerInterface
{
    // ...
    
    public function getPriority(): int
    {
        return 10;
    }
}
```

When multiple transformers can be applied, the one with the highest priority will be used.
