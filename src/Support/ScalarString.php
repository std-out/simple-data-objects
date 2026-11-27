<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use InvalidArgumentException;

final class ScalarString
{
    public static function of(#[\SensitiveParameter] mixed $value, string $cast): string
    {
        if (is_string($value) || is_int($value) || is_float($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new InvalidArgumentException("{$cast} expects a string, got ".get_debug_type($value).'.');
    }
}
