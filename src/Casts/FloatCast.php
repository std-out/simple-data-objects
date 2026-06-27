<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class FloatCast implements CastsValue
{
    public function __construct(
        private readonly int $decimals = -1,
    ) {}

    public static function __set_state(array $state): self
    {
        return new self($state['decimals']);
    }

    public function get(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $float = (float) $value;

        return $this->decimals >= 0 ? round($float, $this->decimals) : $float;
    }

    public function set(mixed $value): ?float
    {
        return $this->get($value);
    }
}
