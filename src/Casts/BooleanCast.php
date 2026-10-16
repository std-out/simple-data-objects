<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;

final class BooleanCast implements CastsValue, ProvidesJsonSchema
{
    private const array TRUTHY = ['true', '1', 'yes', 'on'];

    /** @param  array<string, mixed>  $state */
    public static function __set_state(array $state): self
    {
        return new self;
    }

    public function get(#[\SensitiveParameter] mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), self::TRUTHY, true);
    }

    public function set(#[\SensitiveParameter] mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }

    /** @return array<string, mixed> */
    public function jsonSchema(): array
    {
        return ['type' => 'boolean'];
    }
}
