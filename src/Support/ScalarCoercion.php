<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use InvalidArgumentException;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;

final class ScalarCoercion
{
    private const float INT_LIMIT = 9.2233720368547758E18;

    /**
     * @param  array<mixed>  $d
     * @param  array<int, ParameterMeta>  $parameters
     * @return array<mixed>|null
     */
    public static function input(#[\SensitiveParameter] array $d, array $parameters): ?array
    {
        $coerced = null;

        foreach ($parameters as $param) {
            if ($param->scalarType === null || ! $param->isPlain) {
                continue;
            }

            foreach ($param->inputNames as $name) {
                if (! array_key_exists($name, $d) || self::fits($d[$name], $param)) {
                    continue;
                }

                $coerced ??= $d;
                $coerced[$name] = self::coerce($d[$name], $param->scalarType, (string) $param->inputNames[0]);
            }
        }

        return $coerced;
    }

    public static function coerce(#[\SensitiveParameter] mixed $value, string $type, string $field): int|float|string|bool
    {
        $coerced = match ($type) {
            'int' => self::toInt($value),
            'float' => self::toFloat($value),
            'string' => is_string($value) || is_int($value) || is_float($value) || $value instanceof \Stringable ? (string) $value : null,
            default => is_bool($value) ? $value : (is_string($value) || is_int($value) ? filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) : null),
        };

        return $coerced ?? throw DataHydrationException::invalidValue(
            $field,
            new InvalidArgumentException('Cannot convert '.get_debug_type($value)." to {$type} without loss."),
        );
    }

    private static function fits(#[\SensitiveParameter] mixed $value, ParameterMeta $param): bool
    {
        return ($value === null && $param->allowsNull) || match ($param->scalarType) {
            'int' => is_int($value),
            'float' => is_float($value) || is_int($value),
            'string' => is_string($value),
            default => is_bool($value),
        };
    }

    private static function toInt(#[\SensitiveParameter] mixed $value): ?int
    {
        return match (true) {
            is_int($value) => $value,
            is_float($value) => $value >= -self::INT_LIMIT && $value < self::INT_LIMIT && $value === (float) (int) $value ? (int) $value : null,
            is_string($value) => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            default => null,
        };
    }

    private static function toFloat(#[\SensitiveParameter] mixed $value): int|float|null
    {
        return match (true) {
            is_int($value), is_float($value) => $value,
            is_string($value) && is_numeric($value) && is_finite((float) $value) => (float) $value,
            default => null,
        };
    }
}
