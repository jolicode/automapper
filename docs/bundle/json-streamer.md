# JSON Streamer integration

> [!WARNING]
> The JSON streamer integration is in an experimental state, and may change in the future.
> Some behavior may not be handled correctly, and some features may not be implemented.
>
> If you find a bug or missing feature, please report it on the [issue tracker](https://github.com/jolicode/automapper/issues).

This bundle can replace the reader and the writer of the
[JsonStreamer component of Symfony](https://symfony.com/doc/current/serializer.html#json-streamer)
with implementations backed by the AutoMapper.

Instead of hydrating and encoding objects on its own, the JSON streamer delegates to a generated
AutoMapper mapper. All the AutoMapper features stay available while streaming JSON: property
renaming, `#[MapTo]` / `#[MapFrom]`, custom transformers, providers, discriminators, groups, date
time formats, ...

## Enabling it

Enable both the Symfony component and the AutoMapper integration:

```yaml
# config/packages/framework.yaml
framework:
  json_streamer:
    enabled: true

# config/packages/automapper.yaml
automapper:
  json_streamer:
    enabled: true
```

The AutoMapper services **decorate** the Symfony ones, they do not remove them: any type that the
AutoMapper does not handle (scalars, enums, `DateTimeInterface`, `DateInterval`, `DateTimeZone`, ...)
is still streamed by the Symfony implementation. You keep injecting the standard interfaces:

```php
use Symfony\Component\JsonStreamer\StreamReaderInterface;
use Symfony\Component\JsonStreamer\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;

final readonly class UserController
{
    public function __construct(
        private StreamReaderInterface $streamReader,
        private StreamWriterInterface $streamWriter,
    ) {
    }

    public function __invoke(mixed $stream): iterable
    {
        // JSON -> objects
        $user = $this->streamReader->read($stream, Type::object(User::class));

        // objects -> JSON, chunk by chunk
        return $this->streamWriter->write($user, Type::object(User::class));
    }
}
```

## Only using registered mappings

By default every object type goes through the AutoMapper. If you would rather opt in explicitly, set
`only_registered_mapping` so that only the types you registered are handled by the AutoMapper, the
others falling back to the Symfony implementation:

```yaml
automapper:
  json_streamer:
    enabled: true
    only_registered_mapping: true
  mapping:
    mappers:
      # reading: json -> User, writing: User -> json
      - { source: 'App\Entity\User', target: 'json', reverse: true }
```

The `json` side names the JSON document itself, symmetrically in both directions:

* **reading** (JSON to object) uses a `json` → class mapper;
* **writing** (object to JSON) uses a class → `json` mapper.

Registering the mapping with `reverse: true`, as above, therefore enables both directions.

## Streaming a collection

Reading a JSON array of objects returns a lazy collection: elements are decoded and mapped one at a
time, so a large payload never has to be held in memory at once.

```php
$users = $streamReader->read($stream, Type::list(Type::object(User::class)));

foreach ($users as $user) {
    // $user is a fully mapped User
}
```

By default the collection is buffered, so it can be iterated several times and counted. When you
know you will read it only once, pass the `MapperContext::STREAM` option to keep the memory usage
flat whatever the collection size:

```php
use AutoMapper\MapperContext;

$users = $streamReader->read($stream, Type::list(Type::object(User::class)), [
    MapperContext::STREAM => true,
]);
```

The same option applies to the writer: iterating the returned value yields the JSON chunk by chunk
without ever building the whole string, while casting it to `string` gives you the full payload.

```php
$result = $streamWriter->write($users, Type::list(Type::object(User::class)), [
    MapperContext::STREAM => true,
]);

foreach ($result as $chunk) {
    echo $chunk;
}
```

## Speeding up the decoding with the JSON stream extension

The read path decodes JSON with the `json_stream_decode()` function. It is provided by the
[`joelwurtz/json-stream-polyfill`](https://github.com/joelwurtz/php-json-stream-polyfill) package,
which is installed by default with the AutoMapper, so everything works out of the box.

For better performance you can install the [native PHP extension](https://github.com/joelwurtz/php-json-stream)
instead, which implements the same function in C. Use [pie](https://github.com/php/pie) to install it:

```shell
pie install joelwurtz/json-stream
```

Then enable it in your `php.ini`:

```ini
extension=json_stream
```

Nothing else has to change: the AutoMapper uses the extension automatically as soon as it is loaded,
and falls back to the polyfill when it is not. You can check which one is used with:

```shell
php -r "var_dump(extension_loaded('json_stream'));"
```
