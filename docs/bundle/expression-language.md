# Expression Language

AutoMapper allow you to use the Symfony Expression Language in some places to make your mapping more flexible.

When using the Bundle, it add severals function that you can use in the expression language.

## env

The `env` function allow you to access the environment variables.

```php
class Entity
{
    #[MapTo('array', if: "env('FEATURE_ENABLED')")]
    public string $name;
}
```

## service

The `service` function allow you to call a service from the container.

First, add the `#[AsAutoMapperExpressionService]` attribute to your service.

```php
namespace App\Service;

use AutoMapper\Symfony\Attribute\AsAutoMapperExpressionService;

#[AsAutoMapperExpressionService(alias: 'my_service')
class MyService
{
    public function transform(Entity $entity): string
    {
        return 'transformed';
    }
    
    public function check(): bool
    {
        return false;
    }
}
```

Then, use the `service()` function to refer that service inside your expression.

```php
class Entity
{
    #[MapTo(transformer: "service('my_service').transform(source)'")]
    public string $name;
    
    // Or without an alias
    #[MapTo(transformer: "service('App\\\Service\\\MyService').transform(source)'")]
    public string $name;
    
    #[MapTo(if: "service('my_service').check()")]
    public int $age;
}
```