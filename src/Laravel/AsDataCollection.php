<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel;

use Illuminate\Contracts\Database\Eloquent\Castable;
use StdOut\SimpleDataObjects\BaseData;

/**
 * Casts a JSON array column to a `TypedDataCollection` of the given data
 * class, mirroring Laravel's own `AsCollection::using()` idiom.
 */
final class AsDataCollection implements Castable
{
    public static function castUsing(array $arguments): DataCollectionCast
    {
        return new DataCollectionCast($arguments[0]);
    }

    /** @param class-string<BaseData> $dataClass */
    public static function of(string $dataClass): string
    {
        return self::class.':'.$dataClass;
    }
}
