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

> [!NOTE]
> When using the Symfony Bundle version of the AutoMapper, you can use the `automapper.property_transformer` tag to 
> register the transformer.
> 
> If you have autoconfiguration enabled, you do not need to register the transformer manually as the tag will be 
> automatically added.

Then you can use it in the `transformer` argument.

```php
use App\Transformer\UrlTransformer;

class Source
{
    #[MapTo(property: 'url', transformer: UrlTransformer::class)]
    public int $id;
}
```

> [!NOTE]
> When using the Symfony Bundle version of the AutoMapper, the transformer will be the service id, which may be different
> from the class name.

### Automatically apply custom transformers

You may want to automatically apply a custom transformer given a specific condition.
In order to do so, you can implement the `PropertyTransformerSupportInterface` interface.

```php
namespace App\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

class UrlTransformer implements PropertyTransformerSupportInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    
    public function supports(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return $source->type->isIdentifiedBy(TypeIdentifier::INT && $source->property === 'id' && $target->property === 'url';
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

class UrlTransformer implements PropertyTransformerSupportInterface, PrioritizedPropertyTransformerInterface
{
    // ...
    
    public function getPriority(): int
    {
        return 10;
    }
}
```

When multiple transformers can be applied, the one with the highest priority will be used.

### Computing extra data for the transformer

In some cases you may want to compute extra data that will be passed to the transformer. This is possible by 
implementing the `PropertyTransformerDataProviderInterface` interface.

```php
namespace App\Transformer;

use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\PropertyTransformer\PropertyTransformerSupportInterface;

class UrlTransformer implements PropertyTransformerComputeInterface
{
    public function __construct()
    {
    }
    
    public function supports(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        return $source->type->isIdentifiedBy(TypeIdentifier::INT && $source->property === 'id' && $target->property === 'url';
    }

    public function compute(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): mixed
    {
        // Compute extra data here
        return 'some computed data';
    }
    
    public function transform(mixed $value, object|array $source, array $context, mixed $computed = null): mixed
    {
        return $computed . $value;
    }
}
```

The value returned by the `compute` method will be created when generating the mapper and passed as a static value to 
the `transform` method each time it is needed.

> [!WARNING]
> This value is only computed when the transformer comes from guessed with the `supports` method. If you specify the transformer
> manually in the `#[MapTo]` or `#[MapFrom]` attribute, the `compute` method will not be called and there will be no value passed
> to the `transform` method.
