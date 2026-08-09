<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;

final class IntegerCast implements CastsValue, ProvidesJsonSchema
{
    public static function __set_state(array $state): self
    {
        return new self;
    }

    public function get(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function jsonSchema(): array
    {
        return ['type' => 'integer'];
    }
}
