# AutoMapper benchmarks

This package benchmarks [jolicode/automapper](https://github.com/jolicode/automapper)
against the Symfony components that solve overlapping problems, and against the
raw PHP `json_encode` / `json_decode` baseline:

- **`json_encode` / `json_decode`** — the theoretical floor (no object hydration).
- **AutoMapper** — in several configurations (see below).
- **AutoMapper JSON streamer** — the `AutoMapper\JsonStreamer\JsonStream{Reader,Writer}`
  bridge that plugs AutoMapper into `symfony/json-streamer`.
- **Symfony Serializer** (`symfony/serializer`).
- **Symfony JSON streamer** (`symfony/json-streamer`).
- **Symfony ObjectMapper** (`symfony/object-mapper`), for the object-to-object case.

It is built on [PHPBench](https://phpbench.readthedocs.io). Every subject uses the
exact same data (see `src/Factory/PayloadFactory.php`) so the numbers are directly
comparable, and each library is wired once, outside the measured loop
(`src/Factory/MapperFactory.php`).

## Installing

```bash
cd bench
composer install
```

The package depends on the checked-out AutoMapper via a Composer `path` repository,
so it always benchmarks your working copy.

## Running

```bash
# Everything, with timing + peak memory
php vendor/bin/phpbench run --report=aggregate

# A single scenario
php vendor/bin/phpbench run src/DeserializeBench.php --report=aggregate

# Focus on memory (custom report defined in phpbench.json)
php vendor/bin/phpbench run src/CollectionBench.php --report=memory

# By group
php vendor/bin/phpbench run --group=denormalize --report=aggregate
php vendor/bin/phpbench run --group=normalize --report=aggregate
php vendor/bin/phpbench run --group=deserialize --report=aggregate
php vendor/bin/phpbench run --group=serialize --report=aggregate
php vendor/bin/phpbench run --group=object-to-object --report=aggregate
php vendor/bin/phpbench run --group=collection --report=memory
php vendor/bin/phpbench run --group=write-collection --report=memory
php vendor/bin/phpbench run --group=write-wide-object --report=memory
```

## Verifying correctness

A benchmark is only fair if every approach in a group produces the **same result**
for the same input. That is enforced two ways:

- `bin/verify.php` runs each group and prints an `ok` / `DIFF` report, exiting
  non-zero on any divergence:

  ```bash
  php bin/verify.php
  ```

- Every benchmark also calls the matching `Verifier::assert*()` from its `setUp()`,
  so `phpbench run` fails fast if any approach drifts out of agreement.

Results are compared *canonically* — normalized to arrays with keys sorted
recursively — so an approach that emits object keys in a different order (AutoMapper
sorts them, for instance) still counts as equal as long as the data matches. See
`src/Verifier.php`.

## Scenarios

The four (de)serialization suites are split so each measures exactly one thing, and
every subject in a suite has the **same input and output shape**:

| Suite | File | Direction | JSON step? |
|-------|------|-----------|------------|
| Denormalize | `src/DenormalizeBench.php` | array → `Person` object | no |
| Normalize | `src/NormalizeBench.php` | `Person` object → array | no |
| Deserialize | `src/DeserializeBench.php` | JSON string → `Person` object | yes (`json_decode`) |
| Serialize | `src/SerializeBench.php` | `Person` object → JSON string | yes (`json_encode`) |
| Object to object | `src/ObjectToObjectBench.php` | `PersonSource` → `PersonTarget` | — |
| Collection (read) & memory | `src/CollectionBench.php` | large JSON array → `Person` list | **read `mem_peak`** |
| Collection (write) & memory | `src/WriteCollectionBench.php` | `Person` list → large JSON array | **read `mem_peak`** |
| Wide-object write & memory | `src/WriteWideObjectBench.php` | one `Person` with a huge nested list → JSON | **read `mem_peak`** |

Normalize/Denormalize isolate the pure mapping cost; Serialize/Deserialize add the
`json_encode`/`json_decode` step on top, so the difference between the two is the
JSON cost. The JSON streamers only appear in the JSON suites (they have no
array-producing/consuming step).

### Symfony services are wired like the framework

The Symfony Serializer and ObjectMapper are built to match FrameworkBundle's default
services (see `src/Factory/MapperFactory.php`), not a stripped-down minimum:

- **Serializer** — `ObjectNormalizer` with a cached `ClassMetadataFactory`
  (`CacheClassMetadataFactory`), a cached `property_info` (`PropertyInfoCacheExtractor`)
  and a cached `PropertyAccessor`, exactly as `serializer.normalizer.object` is configured.
- **ObjectMapper** — the internally-caching `ReflectionObjectMapperMetadataFactory`
  plus a cached `PropertyAccessor`, as the `object_mapper` service is configured.

Note this makes the Symfony numbers *higher*, not lower, than a bare hand-built
setup: the `ClassMetadataFactory` and `PropertyAccessor` the framework always wires
add real per-call cost, and the metadata caches don't help a warm, instance-reused
benchmark (the normalizer already memoizes type info internally). Using the real
default services is the fair comparison — an application pays this cost.

### AutoMapper configurations compared

The **denormalize** suite exercises several AutoMapper `Configuration` variants so you
can see what each knob costs — the config only affects the mapping step, so that is
where it is measured (see `src/Factory/MapperFactory.php`):

- **default** — `FileLoader` on-disk cache, `ConstructorStrategy::AUTO` (out of the box).
- **eval** — `EvalLoader`, no on-disk cache (mappers eval'd into the process).
- **no constructor** — `ConstructorStrategy::NEVER` (writes properties directly).
- **no attribute checking** — `attributeChecking: false`, `mapPrivateProperties: false`.
- **no checking** — the above plus `groupChecking: false`.

## Results

Numbers below were measured on PHP 8.5 with the `json_stream` extension loaded (see
[the note on the extension](#the-json_stream-extension-matters-on-the-read-path)).
Your absolute numbers will differ; the *ratios* are the point. Lower is better.

### Single object — denormalize (array → object, no JSON)

| Approach | Time | vs AutoMapper |
|----------|-----:|--------------:|
| AutoMapper, no attribute checking | ~2.9 µs | fastest mapper |
| AutoMapper, eval loader | ~7.1 µs | — |
| AutoMapper, no constructor | ~7.1 µs | — |
| AutoMapper (default) | ~7.2 µs | 1× |
| Symfony Serializer (`denormalize`) | ~165 µs | **~23× slower** |

### Single object — normalize (object → array, no JSON)

| Approach | Time |
|----------|-----:|
| AutoMapper, no attribute checking | ~2.5 µs |
| AutoMapper (default) | ~8.0 µs |
| Symfony Serializer (`normalize`) | ~63 µs |

### Single object — deserialize (JSON → object)

Every object-producing subject **fully realizes** the `Person` graph (reads all its
fields) so the work is comparable — see the fairness note below.

| Approach | Time |
|----------|-----:|
| **Manual** (`json_decode` + hand-written hydration — *fair floor*) | ~3.3 µs |
| AutoMapper, no checking (`json_decode` + map) | ~5.8 µs |
| AutoMapper JSON streamer, no attribute checking | ~8.2 µs |
| AutoMapper (`json_decode` + map) | ~10.6 µs |
| AutoMapper JSON streamer | ~13.5 µs |
| Symfony JSON streamer | ~156 µs |
| Symfony Serializer | ~169 µs |

The AutoMapper JSON streamer is now **~11× faster than Symfony's** on this shape, and
within ~1.3× of a plain `json_decode` + `map()`. It reads straight from the decoded
document through a generated `json` → class mapper, so nothing is materialized twice.

> **Fairness note.** Symfony's `JsonStreamReader::read()` on a `Type::object` returns a
> *lazy ghost* whose hydration is deferred until a property is read, so a subject that
> never touched the result would clock ~13 µs while actually doing almost nothing.
> Reading the whole graph forces that deferred work.

### Single object — serialize (object → JSON)

| Approach | Time |
|----------|-----:|
| **Manual** (hand-written normalization + `json_encode` — *fair floor*) | ~1.3 µs |
| AutoMapper, no attribute checking (map + `json_encode`) | ~3.6 µs |
| AutoMapper, no checking (map + `json_encode`) | ~3.8 µs |
| AutoMapper JSON streamer, no attribute checking | ~7.4 µs |
| Symfony JSON streamer | ~9.2 µs |
| AutoMapper (map + `json_encode`) | ~9.5 µs |
| AutoMapper JSON streamer | ~14.2 µs |
| Symfony Serializer | ~69 µs |

The **manual** row is the fair baseline: it produces/consumes the same `Person` graph
the libraries do, just by hand (see `src/ManualMapper.php`).

### Object to object

| Approach | Time |
|----------|-----:|
| Manual (hand-written) | ~0.23 µs |
| AutoMapper, no attribute checking | ~1.6 µs |
| AutoMapper | ~5.0 µs |
| AutoMapper ObjectMapper bridge | ~5.5 µs |
| Symfony ObjectMapper | ~49 µs (**~10× slower**) |

### Collection — read a list of `Person` (`src/CollectionBench.php`)

Reading a JSON array of `Person`, **every object fully hydrated** (the loop reads a
nested field so each library does the same work). Peak memory (`mem_peak`) and time:

| Approach | 1 000 | 20 000 | time (20k) |
|----------|------:|-------:|-----------:|
| `json_decode` (array only, no objects) | 14 MB | 93 MB | 0.09 s |
| AutoMapper `mapCollection`, no checking (eager) | 16 MB | 108 MB | 0.21 s |
| AutoMapper `mapCollection` (eager) | 15 MB | 107 MB | 0.29 s |
| **AutoMapper JSON streamer, `STREAM`, no checking** | 11 MB | **21 MB** | **0.15 s** |
| **AutoMapper JSON streamer, `STREAM`** | 11 MB | **21 MB** | 0.25 s |
| AutoMapper JSON streamer, buffered, no attr checking | 18 MB | 183 MB | 0.25 s |
| AutoMapper JSON streamer, buffered | 18 MB | 183 MB | 0.37 s |
| Symfony Serializer | 14 MB | 101 MB | 3.45 s |
| Symfony JSON streamer, **iterable** (lazy) | 10 MB | **10 MB** | 4.14 s |
| Symfony JSON streamer, `list` (materialized) | 43 MB | 682 MB | 4.82 s |

This is where the `json` source pays off the most: streaming through the AutoMapper is
**~16–27× faster than Symfony's streamer** (0.15–0.25 s vs 4.14 s) while staying within
~2× of its memory, and it is *also faster than eagerly decoding the whole array* with
`mapCollection`. Unlike on the read path before, disabling the checks now matters again
(0.25 s → 0.15 s), because the decoding is no longer the bottleneck.

Use `STREAM` for large inputs: buffered mode memoizes every mapped object (183 MB at
20k) to stay countable and re-iterable, while `STREAM` keeps a bounded peak at the cost
of being single-pass.

#### `iterable` vs `list`: the type drives whether Symfony actually streams

The same JSON array read by the same Symfony reader costs **~10 MB or ~682 MB**
depending only on the requested type:

- **`Type::iterable(Type::object(Person::class), Type::int())`** → `read()` returns a
  `Generator` that decodes and yields one hydrated `Person` at a time. Flat, ~10 MB.
- **`Type::list(Type::object(Person::class))`** → the generated reader ends with
  `iterator_to_array(...)`, materializing the whole collection. Each element is a
  `ReflectionClass::newLazyGhost()` whose initializer closure captures its own
  *suspended* boundary generator (lexer + stream state). At 20 000 elements that is
  ~20 000 ghosts + ~20 000 live generators retained at once. It holds even if the
  ghosts are never hydrated. **Prefer `iterable`.**

The `int` key type is what tells Symfony to read a JSON array (`[…]`) rather than an
object (`{…}`) — omit it and the reader expects `{…}` and throws on a list.

### Collection — write a list of `Person` (`src/WriteCollectionBench.php`)

Serializing a list of `Person` (fed from a generator) to JSON, comparing the two JSON
stream **writer** implementations:

| Writer / consumption | 1 000 | 20 000 | time (20k) |
|----------------------|------:|-------:|-----------:|
| **Symfony JSON streamer — `getIterator()` (streamed)** | **7.2 MB** | **7.2 MB** | **0.10 s** |
| Symfony JSON streamer — `__toString()` | 7.5 MB | 16 MB | 0.12 s |
| AutoMapper `mapCollection` + `json_encode` (eager) | 11 MB | 77 MB | 0.14 s |
| **AutoMapper JSON streamer — `STREAM=true` (streamed)** | 7.8 MB | **7.8 MB** | 0.15 s |
| AutoMapper JSON streamer — `__toString()` | 8.2 MB | 17 MB | 0.17 s |

Both streamed writers keep a **flat peak** whatever the collection size, and the
AutoMapper one is now within ~1.5× of Symfony's while running every element through the
full mapping pipeline. The eager `mapCollection` + `json_encode` path is comparable in
time but allocates the whole array-of-arrays (77 MB at 20k).

### Collection — write one object with a big nested collection (`src/WriteWideObjectBench.php`)

A single `Person` whose `addresses` is huge, so the nested collection is what streams:

| Writer / consumption | 5 000 | 50 000 | time (50k) |
|----------------------|------:|-------:|-----------:|
| **Symfony JSON streamer — `getIterator()`** | **9.5 MB** | **23 MB** | **0.038 s** |
| Symfony JSON streamer — `__toString()` | 9.8 MB | 27 MB | 0.047 s |
| AutoMapper — `getIterator()` `STREAM=true`, no attr | 9.5 MB | 23 MB | 0.052 s |
| AutoMapper — `__toString()`, no attr checking | 9.8 MB | 27 MB | 0.062 s |
| AutoMapper — `getIterator()` `STREAM=true` | 9.5 MB | 23 MB | 0.10 s |
| AutoMapper — `__toString()` (buffered) | 9.8 MB | 27 MB | 0.11 s |

Memory is now **identical to Symfony** in both consumption modes: the nested collection
is streamed through the sub-mappers instead of being encoded whole. Disabling attribute
checking gives a **~2× write-side speed-up** here, because encoding is pure AutoMapper
with no decoding in the path.

#### Why Symfony's writer is still faster at collection scale

For a *single* small object the two writers are close (serialize: ~14 µs vs ~9 µs), so
the collection gap comes down to **per-element cost**, which a collection multiplies by
`N`. Measured marginal cost per extra nested element:

| | per element |
|---|------:|
| AutoMapper writer (`STREAM`) | **~2.2 µs** |
| Symfony writer (`getIterator`) | **~0.9 µs** |

AutoMapper pays ~2.4× more per element (it was ~5× before the generated `json` mappers
replaced the lazy-structure walk). Symfony emits from a compiled, type-specialized
writer that reads fields straight off the object; the AutoMapper one additionally runs
each element through the mapping pipeline. Reach for it when you need that pipeline
(renames, transformers, `#[MapTo]`, private properties, discriminators, …); for a plain
object graph with no mapping, Symfony's writer is still the faster tool.

### The `json_stream` extension matters on the read path

`json_stream_decode()` comes either from the
[`json_stream` PHP extension](https://github.com/joelwurtz/php-json-stream) or from the
pure-PHP [polyfill](https://github.com/joelwurtz/php-json-stream-polyfill) that ships
with the AutoMapper. Same single-object read, same code:

| Decoder | Time |
|---------|-----:|
| `json_stream` extension | ~14 µs |
| polyfill (pure PHP) | ~93 µs |

That is a **~6.7× difference on the read path**, so all the read numbers above assume
the extension. Install it with [pie](https://github.com/php/pie):

```shell
pie install joelwurtz/json-stream
```

The write path does not decode anything and is unaffected.
