# Upgrading from 8.x to 9.0

9.0 is major release of AutoMapper. It brings a lot of new features and improvements. We recommend first [to check 
the new documentation](./index.md) to see if the new features are useful for your project.

If you upgrade from 8.x to 9.0, you will need to make some changes to your code, but most of existing behavior should
still work.

## Bundle

If you use the bundle, it is now integrated in the main package. You can remove the `jolicode/automapper-bundle` package from your
`composer.json` file.

Then you have to use the new namespace for the bundle:

```php
use AutoMapper\Symfony\Bundle\AutoMapperBundle;
```

You will also need to update the bundle configuration, see the [bundle documentation](./bundle/configuration.md) for more
information.

## Custom Transformers

The `CustomPropertyTransformerInterface` and `CustomModelTransformerInterface` have been removed in favor of the 
`PropertyTransformerInterface` interface handling both case.

See the [transformers documentation](./mapping/transformer.md#creating-a-custom-transformer) for more information.
