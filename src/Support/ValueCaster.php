<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use BackedEnum;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\TypedDataCollection;
use UnitEnum;

final class ValueCaster
{
    public static function cast(ParameterMeta $meta, mixed $value): mixed
    {
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

            return $meta->dataCollectionClass::collection($value);
        }

        if ($meta->nestedDataClass !== null && $value !== null) {
            return $value instanceof $meta->nestedDataClass ? $value : $meta->nestedDataClass::from($value);
        }

        if ($meta->enumClass !== null && $value !== null && ! ($value instanceof UnitEnum)) {
            return self::castEnum($meta, $value);
        }

        return $value;
    }

    private static function castEnum(ParameterMeta $meta, mixed $value): ?UnitEnum
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

        if ($enum === null && ! $meta->allowsNull) {
            throw DataHydrationException::invalidEnumValue($meta->enumClass, $meta->inputName, $value);
        }

        return $enum;
    }
}
