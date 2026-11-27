<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;

final class IntegerCast implements CastsValue, ProvidesJsonSchema
{
    /** @param  array<string, mixed>  $state */
    public static function __set_state(array $state): self
    {
        return new self;
    }

    public function get(#[\SensitiveParameter] mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (\is_array($value) || \is_object($value)) {
            throw new InvalidArgumentException('IntegerCast expects a scalar, got '.get_debug_type($value).'.');
        }

        return (int) $value;
    }

    public function set(#[\SensitiveParameter] mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    /** @return array<string, mixed> */
    public function jsonSchema(): array
    {
        return ['type' => 'integer'];
    }
}
