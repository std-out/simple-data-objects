<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\TypedDataCollection;
use UnitEnum;

final class ValueCaster
{
    public static function cast(ParameterMeta $meta, mixed $value): mixed
    {
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
            return $meta->nestedDataClass::from($value);
        }

        if ($meta->enumClass !== null && $value !== null && ! ($value instanceof UnitEnum)) {
            return $meta->enumClass::tryFrom($value);
        }

        return $value;
    }
}
