<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Concerns;

use StdOut\SimpleDataObjects\Laravel\DataObjectCast;

/**
 * Structurally satisfies `\Illuminate\Contracts\Database\Eloquent\Castable`
 * so a data object can be assigned directly as an Eloquent attribute cast.
 *
 * Does not `implements Castable` itself, and has no dependency on
 * illuminate/database — the consuming class adds
 * `implements \Illuminate\Contracts\Database\Eloquent\Castable` itself.
 * The actual caster lives under `Laravel\` and is only autoloaded once
 * `castUsing()` runs, i.e. only inside a real Eloquent model.
 */
trait IsEloquentCastable
{
    public static function castUsing(array $arguments): DataObjectCast
    {
        return new DataObjectCast(static::class);
    }
}
