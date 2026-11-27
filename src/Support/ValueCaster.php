<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use BackedEnum;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\TypedDataCollection;
use UnitEnum;

final class ValueCaster
{
    public static function cast(ParameterMeta $meta, #[\SensitiveParameter] mixed $value): mixed
    {
        try {
            if ($meta->pipes !== []) {
                $value = PipelineRunner::runOnValue($value, $meta->phpName, $meta->pipes);
            }

            if ($meta->caster !== null) {
                return $meta->caster->get($value);
            }

            if ($meta->dataCollectionClass !== null) {
                if ($value === null) {
                    return $meta->allowsNull ? null : new TypedDataCollection;
                }

                return is_iterable($value)
                    ? $meta->dataCollectionClass::collection($value)
                    : throw new \InvalidArgumentException('A data collection expects an array, got '.get_debug_type($value).'.');
            }

            if ($meta->nestedDataClass !== null && $value !== null) {
                return $value instanceof $meta->nestedDataClass ? $value : $meta->nestedDataClass::from($value);
            }

            if ($meta->enumClass !== null && $value !== null && ! ($value instanceof UnitEnum)) {
                return self::castEnum($meta, $value);
            }

            return $value;
        } catch (DataHydrationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw DataHydrationException::invalidValue((string) $meta->inputNames[0], $e);
        }
    }

    public static function castSensitive(ParameterMeta $meta, #[\SensitiveParameter] mixed $value, string $class): mixed
    {
        try {
            return self::cast($meta, $value);
        } catch (\Throwable) {
            throw DataHydrationException::invalidSensitiveValue($class, (string) $meta->inputNames[0]);
        }
    }

    private static function castEnum(ParameterMeta $meta, #[\SensitiveParameter] mixed $value): UnitEnum
    {
        $enum = null;

        if (is_subclass_of($meta->enumClass, BackedEnum::class)) {
            if (is_int($value) || is_string($value)) {
                $enum = $meta->enumClass::tryFrom($value);
            }
        } elseif (is_string($value)) {
            // Pure (non-backed) enums have no tryFrom(); match by case name
            foreach ($meta->enumClass::cases() as $case) {
                if ($case->name === $value) {
                    $enum = $case;
                    break;
                }
            }
        }

        return $enum ?? throw DataHydrationException::invalidEnumValue($meta->enumClass, (string) $meta->inputNames[0], $value);
    }
}
