# Upgrading to 10.0

10.0 is major release of AutoMapper. It aims at supporting the new [`TypeInfo` Component](https://symfony.com/doc/current/components/type_info.html) of Symfony

There is little change between 9.X and 10.0 version, but they are still a BC break so it need a major version.

## Custom Transformers

If you were using the `PropertyTransformerSupportInterface` interface, its signature has changed to use the new `TypeInfo` component.

Before: 

```php
class MyTransformer implements PropertyTransformerSupportInterface
{
    public function supports(TypesMatching $types, SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        // ...
    }
}
```

After:

```php
class MyTransformer implements PropertyTransformerSupportInterface
{
    public function supports(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        // ...
    }
}
```

There is no more `TypesMatching` argument, you can get the source and target types from the `SourcePropertyMetadata` and `TargetPropertyMetadata` arguments, 
which are instance of `Symfony\Component\TypeInfo\Type` class.

```php
class MyTransformer implements PropertyTransformerSupportInterface
{
    public function supports(SourcePropertyMetadata $source, TargetPropertyMetadata $target, MapperMetadata $mapperMetadata): bool
    {
        if ($source->type->isNullable()) {
            return false;
        }
        
        // ...
    }
}
```

See the [transformers documentation](../mapping/transformer.md#creating-a-custom-transformer) for more information.
