<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use StdOut\SimpleDataObjects\BaseData;
use UnitEnum;

/**
 * Converts a property value into its array/scalar representation for
 * serialization. Static so compiled serializer closures can call it.
 */
final class ValueNormalizer
{
    public static function normalize(mixed $value): mixed
    {
        if ($value instanceof BaseData) {
            return $value->toArray();
        }

        // Plain foreach: no closure allocation or map() machinery per element
        if ($value instanceof Collection) {
            $items = [];
            foreach ($value as $key => $item) {
                $items[$key] = self::normalize($item);
            }

            return $items;
        }

        if (is_array($value)) {
            $items = [];
            foreach ($value as $key => $item) {
                $items[$key] = self::normalize($item);
            }

            return $items;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }
}
