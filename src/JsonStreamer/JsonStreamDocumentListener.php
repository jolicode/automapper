<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer;

use AutoMapper\Event\GenerateMapperEvent;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Extractor\ArrayReadAccessor;

/**
 * Teaches the AutoMapper to read the native JSON streamer document ({@see \JsonStream\Document},
 * produced by the `json_stream_decode()` extension function) as an array-like source.
 *
 * The document is a read-only `ArrayAccess` object that exposes the decoded JSON by key, exactly
 * like the pure-PHP {@see Read\LazyJsonObject} — but being a native class it cannot implement
 * {@see \AutoMapper\Lazy\LazyMapInterface}, so the default inference does not recognize it. This
 * listener supplies, for that one class, what the interface would otherwise provide:
 *
 *  - {@see onGenerateMapper} marks the mapper as array-like, so the "from target" extractor is used;
 *  - {@see onPropertyMetadata} reads each property through `offsetGet` (an {@see ArrayReadAccessor}).
 *
 * @internal
 */
final readonly class JsonStreamDocumentListener
{
    public const DOCUMENT_CLASS = 'JsonStream\\Document';

    public function onGenerateMapper(GenerateMapperEvent $event): void
    {
        if (is_a($event->mapperMetadata->source, self::DOCUMENT_CLASS, true)) {
            $event->sourceArrayLike ??= true;
        }

        if (is_a($event->mapperMetadata->target, self::DOCUMENT_CLASS, true)) {
            $event->targetArrayLike ??= true;
        }
    }

    public function onPropertyMetadata(PropertyMetadataEvent $event): void
    {
        if (is_a($event->mapperMetadata->source, self::DOCUMENT_CLASS, true)) {
            $event->source->accessor ??= new ArrayReadAccessor($event->source->property, isArrayAccess: true);
            // Guard reads with offsetExists so a missing key is skipped rather than fetched (which
            // would emit an "undefined key" warning on the read-only document).
            $event->source->checkExists ??= true;
        }
    }
}
