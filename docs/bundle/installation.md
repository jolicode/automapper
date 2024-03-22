# Installing the Symfony Bundle

The bundle is already available on the `jolicode/automapper` package, you don't need to add any packages to your composer.json file.

## Registering the bundle

To use it, you have to register the main bundle class in your `config/bundles.php` file.

```php
return [
    // ...
    AutoMapper\Symfony\Bundle\AutoMapperBundle::class => ['all' => true],
];
```

## Usage

Once the bundle is registered, you can use the `AutoMapperInterface` service to map your objects.

```php
use AutoMapper\AutoMapperInterface;

class MyController
{
    public function __construct(private AutoMapperInterface $autoMapper)
    {
    }

    #[Route('/my-route', name: 'my_route')]
    public function index()
    {
        $source = new Source();
        $target = $this->autoMapper->map($source, 'array');

        return new JsonResponse($target);
    }
}
```