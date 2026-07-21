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

## Results

Indicative numbers on PHP 8.5 (your absolute numbers will differ; the *ratios* are
the point). Lower is better.

### Single object — denormalize (array → object, no JSON)

| Approach | Time | vs AutoMapper |
|----------|-----:|--------------:|
| AutoMapper, no attribute checking | ~3.0 µs | fastest mapper |
| AutoMapper (default) | ~7.4 µs | 1× |
| AutoMapper, eval loader | ~7.3 µs | — |
| AutoMapper, no constructor | ~7.4 µs | — |
| Symfony Serializer (`denormalize`) | ~167 µs | **~23× slower** |

### Single object — normalize (object → array, no JSON)

| Approach | Time |
|----------|-----:|
| AutoMapper, no attribute checking | ~2.9 µs |
| AutoMapper (default) | ~8.2 µs |
| Symfony Serializer (`normalize`) | ~66 µs |

### Single object — deserialize (JSON → object)

Every object-producing subject **fully realizes** the `Person` graph (reads all its
fields) so the work is comparable — see the fairness note below.

| Approach | Time |
|----------|-----:|
| `json_decode` (array only, no hydration — *pure floor*) | ~2.4 µs |
| **Manual** (`json_decode` + hand-written hydration — *fair floor*) | ~3.3 µs |
| AutoMapper, no attribute checking (`json_decode` + map) | ~6 µs |
| AutoMapper (`json_decode` + map) | ~11 µs |
| AutoMapper JSON streamer, no attribute checking | ~91 µs |
| AutoMapper JSON streamer | ~101 µs |
| Symfony JSON streamer | ~155 µs |
| Symfony Serializer | ~167 µs |

> **Fairness note.** Symfony's `JsonStreamReader::read()` on a `Type::object`
> returns a *lazy ghost* whose hydration is deferred until a property is read, so a
> subject that never touched the result would clock ~13 µs while actually doing
> almost nothing. Reading the whole graph forces that deferred work, and the fully
> hydrated cost is ~155 µs. The AutoMapper JSON streamer, which hands back a fully
> mapped object up front, is actually *faster* than the stock streamer once both
> produce a usable object.

Note the two JSON streamers are an order of magnitude slower than plain `map()` for a
single object: they exist to stream **large collections** at flat memory (see below),
and pay a heavy per-call overhead that only amortizes over big inputs. For single
objects, `map()` + native `json_decode` is the right tool.

### Single object — serialize (object → JSON)

| Approach | Time |
|----------|-----:|
| `json_encode` (array only, no traversal — *pure floor*) | ~0.8 µs |
| **Manual** (hand-written normalization + `json_encode` — *fair floor*) | ~1.2 µs |
| AutoMapper, no attribute checking (map + `json_encode`) | ~3.9 µs |
| AutoMapper JSON streamer, no attribute checking | ~8 µs |
| Symfony JSON streamer | ~9 µs |
| AutoMapper (map + `json_encode`) | ~10 µs |
| AutoMapper JSON streamer | ~14 µs |
| Symfony Serializer | ~66 µs |

The AutoMapper JSON streamer writer was ~25 µs here until its `__toString()` was
changed to encode the lazy structure in one native `json_encode` call (both
`LazyMap` and `LazyCollection` are `JsonSerializable`) instead of concatenating
hand-built chunks — a ~1.85× speed-up (2.5× with attribute checking off), with
byte-identical output. Chunk streaming is still used when the result is *iterated*
(`getIterator`), for callers writing to an output stream.

The **manual** row is the fair baseline: it produces/consumes the same `Person`
graph the libraries do, just by hand (see `src/ManualMapper.php`). The pure
`json_*` rows never touch a `Person`, so they only show the JSON cost in isolation.

### Object to object

| Approach | Time |
|----------|-----:|
| Manual (hand-written) | ~0.2 µs |
| AutoMapper, no attribute checking | ~1.6 µs |
| AutoMapper | ~4.9 µs |
| AutoMapper ObjectMapper bridge | ~5.4 µs |
| Symfony ObjectMapper | ~48 µs (**~10× slower**) |

### Collection — peak memory (the headline)

Reading a JSON array of `Person` objects, **every object fully hydrated** (the loop
reads a nested field so each library does the same work — see the note below).
Peak memory (`mem_peak`) and time for 20 000 items:

| Approach | 1 000 items | 20 000 items | time (20k) |
|----------|------------:|-------------:|-----------:|
| `json_decode` (array only, no objects) | 11 MB | 90 MB | 0.10 s |
| AutoMapper `mapCollection` (eager) | 14 MB | 107 MB | 0.29 s |
| Symfony Serializer | 14 MB | 101 MB | 3.44 s |
| Symfony JSON streamer, **iterable** (lazy) | 9.8 MB | **9.8 MB** | 4.11 s |
| Symfony JSON streamer, `list` (materialized) | 43 MB | 681 MB | 4.50 s |
| AutoMapper JSON streamer, buffered | 12 MB | 51 MB | 3.16 s |
| AutoMapper JSON streamer, buffered, no attr checking | 12 MB | 51 MB | 2.96 s |
| **AutoMapper JSON streamer, `STREAM` mode** | **9.8 MB** | **9.8 MB** | 3.06 s |
| **AutoMapper JSON streamer, `STREAM`, no attr checking** | **9.8 MB** | **9.8 MB** | 3.11 s |

Disabling attribute checking barely moves the streamer here (buffered 3.16 → 2.96 s,
`STREAM` ~unchanged): unlike the plain `map()` path — where it's a ~2–3× win — the
streamer's time is dominated by Symfony's userland JSON tokenizer decoding each
element, so the AutoMapper mapping step it speeds up is only a small slice. Memory is
identical, since attribute checking is a code-generation concern, not a runtime one.

Both truly-streaming readers — Symfony's `iterable` shape and AutoMapper's `STREAM`
mode — keep a **flat ~10 MB** peak regardless of collection size, because they yield
one object at a time and never hold the whole collection in memory. Streaming trades
throughput for memory: it is slower per element than the eager mappers, so use it
when the input is large enough that memory — not wall time — is the constraint.
AutoMapper's streaming reader is a little faster here and, unlike Symfony's raw
reader, runs each element through the full AutoMapper pipeline (renames, transformers,
`#[MapTo]`, private properties, discriminators, …).

#### `iterable` vs `list`: the type drives whether Symfony actually streams

The same JSON array read by the same reader costs **~10 MB or ~680 MB** depending
only on the requested type:

- **`Type::iterable(Type::object(Person::class), Type::int())`** → `read()` returns a
  `Generator` that decodes and yields one hydrated `Person` at a time. Flat, ~10 MB.
- **`Type::list(Type::object(Person::class))`** → the generated reader ends with
  `iterator_to_array(...)`, materializing the whole collection. Each element is a
  `ReflectionClass::newLazyGhost()` whose initializer closure captures its own
  *suspended* boundary generator (lexer + stream state). At 20 000 elements that is
  ~20 000 ghosts + ~20 000 live generators retained at once — ~30 KB each, hence the
  ~679 MB peak. It holds even if the ghosts are never hydrated. **Prefer `iterable`.**

The `int` key type is what tells Symfony to read a JSON array (`[…]`) rather than an
object (`{…}`) — omit it and the reader expects `{…}` and throws on a list.

> **Fairness note.** The `list` reader returns lazy ghosts, so a benchmark loop that
> never reads a property would measure it deferring all the hydration work the
> other mappers do up front. Every subject in `CollectionBench` therefore reads
> `$person->address->city` to force full realization, so timings are comparable.

> The `STREAM` option (`AutoMapper\MapperContext::STREAM => true`) also makes the
> collection single-pass (it cannot be re-iterated). Without it, the reader buffers
> mapped instances so the collection is countable and re-iterable, at the cost of
> holding them all in memory.

### Collection — write, a list of `Person` (`src/WriteCollectionBench.php`)

The write-side counterpart of the read collection: serialize a list of `Person` to
JSON, comparing the two JSON stream **writer** implementations. AutoMapper's writer
now handles a top-level list of objects (it maps each element through the AutoMapper
pipeline and streams the JSON array; with `STREAM=true` it never buffers the mapped
elements). Peak memory (`mem_peak`) and time:

| Writer / consumption | 1 000 | 20 000 | time (20k) |
|----------------------|------:|-------:|-----------:|
| AutoMapper JSON streamer — `__toString()` (buffered) | 15 MB | 180 MB | 0.63 s |
| **AutoMapper JSON streamer — `STREAM=true` (streamed)** | 8.3 MB | 37 MB | 0.49 s |
| Symfony JSON streamer — `__toString()` | 8.6 MB | 45 MB | 0.10 s |
| **Symfony JSON streamer — `getIterator()` (streamed)** | **8.3 MB** | **36 MB** | **0.07 s** |

Streamed, AutoMapper reaches the **same flat memory as Symfony** (~37 MB at 20k —
essentially just the source list) but is **~6× slower** (0.49 s vs 0.07 s), the same
per-element gap as the wide-object case. Buffered `__toString()` is worse still —
~180 MB, because it resolves the whole list into an array-of-arrays before encoding,
whereas Symfony encodes straight from the objects (~45 MB). So AutoMapper's list
writer is worth it only when you need AutoMapper's mapping on the way out *and* want
bounded memory (use `STREAM`); for a plain list, Symfony's writer is far faster.

### Collection — write, one object with a big nested collection (`src/WriteWideObjectBench.php`)

This is the only shape that exercises the **AutoMapper** JSON stream writer's own
streaming (a single `Person` whose `addresses` is huge). The `STREAM` option applies
on the way out. Peak memory (`mem_peak`):

| Writer / consumption | 5 000 | 50 000 | time (50k) |
|----------------------|------:|-------:|-----------:|
| AutoMapper — `__toString()` (buffered) | 18 MB | 113 MB | 0.17 s |
| AutoMapper — `__toString()`, no attr checking | 18 MB | 113 MB | 0.11 s |
| AutoMapper — `getIterator()` `STREAM=true` | 8.9 MB | 23 MB | 0.21 s |
| AutoMapper — `getIterator()` `STREAM=true`, no attr | 8.9 MB | 23 MB | 0.16 s |
| **Symfony JSON streamer — `__toString()`** | 9 MB | 27 MB | **0.047 s** |
| **Symfony JSON streamer — `getIterator()`** | **8.9 MB** | **23 MB** | **0.036 s** |

`STREAM=true` yields the JSON chunk by chunk and never buffers the mapped
collection, so peak stays low (the residual ~23 MB at 50k is the source object's own
materialized `addresses` array). It is single-pass. Disabling attribute checking
gives a **real ~30 % write-side speed-up** here (unlike the read side), because
encoding is pure AutoMapper — map to a lazy array, then `json_encode`/chunk — with no
Symfony JSON tokenizer in the path to dominate the time.

#### Why Symfony's writer is so much faster at collection scale

For a *single* small object the two writers look close (single-object serialize:
AutoMapper JSON writer ~14 µs vs Symfony ~9 µs), so the collection gap seems
surprising. It comes down to **per-element cost**, which a collection multiplies by
`N`. Measured marginal cost per extra nested element:

| | fixed (0 elements) | per element |
|---|------:|------:|
| AutoMapper writer (`STREAM`) | ~15 µs | **~4.0 µs** |
| Symfony writer (`getIterator`) | ~7 µs | **~0.8 µs** |

So AutoMapper pays ~5× more *per element*. At single-object scale that gap is a fixed
~8 µs that's easy to miss; at 50 000 elements it becomes 50 000 × ~3.2 µs ≈ 160 ms —
the whole difference. The reason: for each element AutoMapper builds a `LazyMap`,
runs the per-item mapping closure, `iterator_to_array()`s it, then `json_encode`s
each scalar field and yields it through nested generators. Symfony's compiled,
type-specialized writer inlines the field encoding straight from the object with none
of that per-element machinery. Reach for the AutoMapper JSON writer when you need its
mapping features (renames, transformers, `#[MapTo]`, …) on the way out; for a plain
object graph, Symfony's writer is the faster tool.
