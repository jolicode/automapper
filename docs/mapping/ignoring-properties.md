# Ignoring properties

Sometimes you may want to ignore a property during the mapping process. This can be done using the `#[MapTo]` or `#[MapFrom]` attributes
with the `ignore` argument set to `true`.

```php
class Source
{
    #[MapTo(target: SourceDTO::class, ignore: true)]
    #[MapTo(target: 'array', ignore: false)]
    public $ignoredProperty;
}
```

Setting `ignore` to `false` may be useful when used in conjunction with the `#[Ignore]` attribute from the Symfony Serializer.

```php
use Symfony\Component\Serializer\Attribute\Ignore;

class Source
{
    #[Ignore]
    #[MapTo(target: SourceDTO::class, ignore: false)]
    public $ignoredProperty;
}
```

In this case the property will be mapped to the `SourceDTO` class, but will be ignored when using the Symfony Serializer.
