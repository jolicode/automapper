# DateTime format

In addition to the default DateTime format you can set in AutoMapper context or in Bundle configuration, you can also
use the `#[MapTo]` and `#[MapFrom]` attributes to define DateTime format of properties that should be mapped.

```php
class Source
{
    #[MapTo(target: 'array', dateTimeFormat: \DateTimeInterface::ATOM)]
    public \DateTimeImmutable $dateTime;
}
```

When doing so the property will be mapped using the given format.
