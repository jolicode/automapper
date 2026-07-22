<?php

declare(strict_types=1);

namespace AutoMapper\JsonStreamer\Read;

/**
 * Low-level, position-based scanning primitives over a {@see JsonBuffer}.
 *
 * Every method takes an absolute offset and returns a new offset (or a value), never mutating
 * shared state, so the higher-level lazy nodes stay re-iterable. Structural values (`{`, `[`) are
 * only *skipped* here (brace matching, no allocation); leaf values (`"..."`, numbers, `true`,
 * `false`, `null`) are decoded through the native {@see json_decode()} so every escape and number
 * format is handled exactly like PHP would.
 *
 * @internal
 */
final class JsonParser
{
    /**
     * Advance past JSON whitespace.
     */
    public static function skipWhitespace(JsonBuffer $buffer, int $pos): int
    {
        while (($c = $buffer->byteAt($pos)) !== null && ($c === ' ' || $c === "\t" || $c === "\n" || $c === "\r")) {
            ++$pos;
        }

        return $pos;
    }

    /**
     * The offset right after the value that starts at $pos (which must sit on the first,
     * whitespace-stripped, byte of the value).
     */
    public static function skipValue(JsonBuffer $buffer, int $pos): int
    {
        $c = $buffer->byteAt($pos);

        if ($c === '{' || $c === '[') {
            return self::skipStructure($buffer, $pos);
        }

        if ($c === '"') {
            return self::skipString($buffer, $pos);
        }

        return self::skipScalar($buffer, $pos);
    }

    /**
     * Decode the value starting at $pos: a scalar becomes its PHP value, while an object or an
     * array becomes a lazy node that is not parsed until it is itself accessed.
     */
    public static function parseValue(JsonBuffer $buffer, int $pos): mixed
    {
        $c = $buffer->byteAt($pos);

        if ($c === '{') {
            return new LazyJsonObject($buffer, $pos);
        }

        if ($c === '[') {
            return new LazyJsonList($buffer, $pos);
        }

        $end = $c === '"' ? self::skipString($buffer, $pos) : self::skipScalar($buffer, $pos);

        return json_decode($buffer->slice($pos, $end - $pos), true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * Match a `{...}` or `[...]` structure and return the offset right after its closing bracket,
     * without decoding its content. Strings are skipped as a whole so brackets inside them do not
     * affect the depth count.
     */
    private static function skipStructure(JsonBuffer $buffer, int $pos): int
    {
        $depth = 0;

        while (($c = $buffer->byteAt($pos)) !== null) {
            if ($c === '"') {
                $pos = self::skipString($buffer, $pos);

                continue;
            }

            if ($c === '{' || $c === '[') {
                ++$depth;
            } elseif ($c === '}' || $c === ']') {
                --$depth;

                if ($depth === 0) {
                    return $pos + 1;
                }
            }

            ++$pos;
        }

        return $pos;
    }

    /**
     * The offset right after the string that starts at the opening quote at $pos.
     */
    private static function skipString(JsonBuffer $buffer, int $pos): int
    {
        ++$pos; // opening quote

        while (($c = $buffer->byteAt($pos)) !== null) {
            if ($c === '\\') {
                $pos += 2; // an escaped char never ends the string (\uXXXX hex digits are plain bytes)

                continue;
            }

            if ($c === '"') {
                return $pos + 1;
            }

            ++$pos;
        }

        return $pos;
    }

    /**
     * The offset right after a scalar literal (number, `true`, `false`, `null`) starting at $pos.
     */
    private static function skipScalar(JsonBuffer $buffer, int $pos): int
    {
        while (($c = $buffer->byteAt($pos)) !== null && $c !== ',' && $c !== '}' && $c !== ']'
            && $c !== ' ' && $c !== "\t" && $c !== "\n" && $c !== "\r") {
            ++$pos;
        }

        return $pos;
    }
}
