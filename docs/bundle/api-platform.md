# Api Platform integration

> [!WARNING]
> The api platform integration is in a experimental state, and may change in the future.
> Some behavior may not be handled correctly, and some features may not be implemented.
>
> If you find a bug or missing feature, please report it on the [issue tracker](https://github.com/jolicode/automapper/issues).

This bundle provides a way to integrate with [Api Platform](https://api-platform.com/) by generating the mappers for you.

It injects extra data in the mappers when we map a Resource class to or from an array.

You have to enable the `api_platform` option in the configuration to use this feature.

If you have custom normalizer with some logic inside you will have to convert this logic with our library way of doing things.
[See our migrate guide](migrate.md) for more information.
