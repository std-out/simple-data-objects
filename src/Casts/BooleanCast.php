<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class BooleanCast implements CastsValue
{
    private const array TRUTHY = ['true', '1', 'yes', 'on'];

    public static function __set_state(array $state): self
    {
        return new self;
    }

    public function get(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), self::TRUTHY, true);
    }

    public function set(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }
}
