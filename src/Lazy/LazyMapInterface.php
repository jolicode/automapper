<?php

declare(strict_types=1);

namespace AutoMapper\Lazy;

/**
 * Marks a source (or target) whose properties are accessed as an array shape through
 * {@see \ArrayAccess} rather than through real object accessors.
 *
 * The AutoMapper treats any implementer like it treats `array`/`\stdClass`: the property list
 * comes from the *other* side of the mapping (see {@see \AutoMapper\Extractor\FromTargetMappingExtractor}),
 * reads go through `offsetGet`/`offsetExists` (see {@see \AutoMapper\Extractor\ArrayReadAccessor}),
 * and nested objects keep the same source type so the laziness propagates recursively.
 *
 * {@see LazyMap} is the eager, callback-backed implementation; other implementations (e.g. a
 * streaming JSON decoder) can parse on demand while reusing the exact same generated mappers.
 *
 * @template TKey
 * @template TValue
 *
 * @extends \ArrayAccess<TKey, TValue>
 */
interface LazyMapInterface extends \ArrayAccess
{
}
