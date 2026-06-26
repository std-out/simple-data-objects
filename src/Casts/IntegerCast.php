<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class IntegerCast implements CastsValue
{
    public function get(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
