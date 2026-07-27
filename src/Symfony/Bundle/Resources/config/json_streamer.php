<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AutoMapper\AutoMapperInterface;
use AutoMapper\JsonStreamer\JsonStreamReader;
use AutoMapper\JsonStreamer\JsonStreamWriter;
use Symfony\Component\JsonStreamer\StreamReaderInterface;
use Symfony\Component\JsonStreamer\StreamWriterInterface;

/*
 * Replaces Symfony's default JSON streamer reader/writer with the AutoMapper ones, by decoration:
 * the decorated (Symfony) service stays available as the fallback, so any type the AutoMapper does
 * not handle keeps being streamed by Symfony itself.
 */
return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('automapper.json_streamer.stream_reader', JsonStreamReader::class)
            ->decorate('json_streamer.stream_reader')
            ->args([
                service(AutoMapperInterface::class),
                service('automapper.json_streamer.stream_reader.inner'),
            ])

        ->set('automapper.json_streamer.stream_writer', JsonStreamWriter::class)
            ->decorate('json_streamer.stream_writer')
            ->args([
                service(AutoMapperInterface::class),
                service('automapper.json_streamer.stream_writer.inner'),
            ])

        // Symfony only aliases the concrete JsonStreamReader/JsonStreamWriter classes for
        // autowiring; alias the interfaces too so they can be injected (and resolve to the
        // decorated services, hence to the AutoMapper implementations).
        ->alias(StreamReaderInterface::class, 'json_streamer.stream_reader')
        ->alias(StreamWriterInterface::class, 'json_streamer.stream_writer')
    ;
};
