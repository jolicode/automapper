# AST transformer factory

> [!WARNING]
> AST transformer factories are for very advanced usage and often not required, please use them with caution

Sometimes we need to manage more complex objects that need specific behavior during mapping. For example the 
`Money\Money` object [from the Money PHP library]((https://github.com/moneyphp/money)) has a lot of properties we 
don't want to manage and can confuse the AutoMapper since it will try to map any properties.

You could do it with a [custom transformer](./transformer.md) but it means you will have to declare each property that
needs to use this custom transformer. That is why AST transformer factory is here, it will automatically generate code 
to map your data thanks to AST.

You can see [such a class in our test suite](https://github.com/jolicode/automapper/blob/main/tests/Fixtures/Transformer/MoneyTransformerFactory.php)
as a working example. You will need to register the factory in the `AutoMapper` instance.

```php
use AutoMapper\AutoMapper;

$autoMapper = AutoMapper::create(transformerFactories: [new MoneyTransformerFactory()]);
```

> [!NOTE]
> When using the Symfony Bundle version of the AutoMapper, you just need to implements the `TransformerFactoryInterface`
> interface and if you have autoconfiguration enabled, you do not need to register the factory manually as the tag will 
> be automatically added.
