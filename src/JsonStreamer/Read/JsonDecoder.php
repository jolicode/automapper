<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer\Read;

/**
 * Entry point of the lazy JSON decoder: turns a JSON input into a lazy value.
 *
 * The returned value is a {@see LazyJsonObject} for an object, a {@see LazyJsonList} for an array,
 * or the decoded PHP scalar otherwise. Nothing below the top level is parsed until it is accessed.
 * A {@see LazyJsonObject} is a {@see \AutoMapper\Lazy\LazyMapInterface}, so it can be fed straight
 * into `AutoMapperInterface::map()` and be read like a source array.
 *
 * @internal
 */
final class JsonDecoder
{
    /**
     * @param resource|string $input
     * @param bool            $streaming when the top-level value is an array, free each element from
     *                                   the buffer once iterated past (single-pass, memory-bounded)
     */
    public static function decode(mixed $input, bool $streaming = false): mixed
    {
        $buffer = new JsonBuffer($input);
        $pos = JsonParser::skipWhitespace($buffer, 0);

        if ($streaming && $buffer->byteAt($pos) === '[') {
            return new LazyJsonList($buffer, $pos, streaming: true);
        }

        return JsonParser::parseValue($buffer, $pos);
    }
}
