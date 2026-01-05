# Migrate an existing application

If you have an existing application with custom normalizers, you may not want to rewrite everything at the same time.

This library provide a way to migrate your existing normalizers with our convention step by step without breaking 
your application.

This can be done by using the `only_registered_mapping` option in the `normalizer` configuration.

```yaml
# config/packages/automapper.yaml
automapper:
    normalizer:
        enabled: true
        only_registered_mapping: true
```

Once this option is enabled, only the normalizers that are registered in the `mappings` configuration will be used.
Others will be ignored and use existing normalizers.

This way, you can migrate your application step by step.

### Selecting a normalizer to migrate

You can first select a class that don't have custom normalizer logic to migrate. This way you can ensure than the 
default behavior of the library is working the same as the `symfony/serializer` component.

You need to register this class in the `mappings` configuration.

```yaml
# config/packages/automapper.yaml
automapper:
    normalizer:
        enabled: true
        only_registered_mapping: true
    mappings:
        mappers:
            - { source: App\Entity\MyEntity, target: 'array' }
```

If you want to migrate the denormalizer, you can use the `reverse` option to register the reverse mapping.

```yaml
# config/packages/automapper.yaml
automapper:
    normalizer:
        enabled: true
        only_registered_mapping: true
    mappings:
        mappers:
            - { source: App\Entity\MyEntity, target: 'array', reverse: true }
```

> [!WARNING]
> If this entity have sub objects, they will also use our library we you normalize from the `App\Entity\MyEntity` class.
> If you normalize only the sub object it will still use the existing normalizer.

### Serializer attributes

If you have serializer attributes on your entity, you can also enabled the `serializer_attributes` option to use them.

```yaml
# config/packages/automapper.yaml
automapper:
    normalizer:
        enabled: true
        only_registered_mapping: true
    serializer: true
    mappings:
        mappers:
            - { source: App\Entity\MyEntity, target: 'array' }
```

### Migrating a custom normalizer

If you have a custom normalizer with some logic inside you will have to convert this logic with our library way of doing
things.

For example, if you have a custom normalizer that add a `virtualProperty` the normalized array, you can use a tansformer
to do the same thing.

```php
#[MapTo(target: 'array', property: 'virtualProperty', transformer: MyTransformer::class)]
class App\Entity\MyEntity
{
    // ...
}
```

See the [transformer documentation](../mapping/transformer.md) for more information on how to achieve that.

Most custom normalizer only need transformers or `#[MapTo]` / `#[MapFrom]` to be converted.

If you have custom logic that may not be converted using our library, please open an issue on the
[github repository](https://github.com/jolicode/automapper) with your use case so we can help you convert it.
