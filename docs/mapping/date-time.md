# DateTime format

There is multiple ways to tell the AutoMapper which DateTime format to use. All theses have their own advantages and 
act differently. Here is a hierarchy of each way to set this format:

- AutoMapper context;
- Property attribute;
- Mapper attribute;
- Symfony Bundle configuration.

Considering this order, if you set a format in a Mapper attribute and in a property attribute, we will use the 
property attribute format because it is a higher priority.

## AutoMapper context

To force a DateTime format everywhere you can use the AutoMapper context with the `MapperContext::DATETIME_FORMAT`:

```php
use AutoMapper\MapperContext;

$source = new Source();
$target = $autoMapper->map($source, 'array', [MapperContext::DATETIME_FORMAT => \DateTimeInterface::ATOM]);
```

> [!NOTE]
> Be aware that by using AutoMapper context, any `#[MapTo]`, `#[MapFrom]`, `#[Mapper]` attribute or bundle configuration 
> will be ignored.

## Property attribute

To set a DateTime format onto a `#[MapTo]` attribute (this will be the same for `#[MapFrom]` attribute), you'll have 
to do as following:

```php
class Source
{
    #[MapTo(dateTimeFormat: \DateTimeInterface::ATOM)]
    public \DateTimeImmutable $dateTime;
}
```

> [!NOTE]
> If you have a `#[Mapper]` attribute onto the same class with a DateTime format set or a Bundle configuration set, it 
> will be ignored and the property attribute configuration will be used.

## Mapper attribute

To set a DateTime format onto a `#[Mapper]` attribute, you'll have to do as following:

```php
#[Mapper(dateTimeFormat: \DateTimeInterface::ATOM)]
class Source
{
    public \DateTimeImmutable $dateTime;
}
```

> [!NOTE]
> If you have a bundle configuration it will be ignored and the mapper attribute will be used.

## Symfony Bundle configuration

```yaml
automapper:
  date_time_format: !php/const:DateTimeInterface::ATOM
```

> [!NOTE]
> If you use an attribute DateTime format or the AutoMapper context, this configuration will be ignored.
