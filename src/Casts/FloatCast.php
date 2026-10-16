<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;

final class FloatCast implements CastsValue, ProvidesJsonSchema
{
    public function __construct(
        private readonly int $decimals = -1,
        private readonly bool $finite = false,
    ) {}

    /** @param  array<string, mixed>  $state */
    public static function __set_state(array $state): self
    {
        return new self($state['decimals'], $state['finite'] ?? false);
    }

    public function get(#[\SensitiveParameter] mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $float = (float) $value;

        if ($this->finite && ! is_finite($float)) {
            throw new InvalidArgumentException('FloatCast(finite: true) rejects INF and NAN.');
        }

        return $this->decimals >= 0 ? round($float, $this->decimals) : $float;
    }

    public function set(#[\SensitiveParameter] mixed $value): ?float
    {
        return $this->get($value);
    }

    /** @return array<string, mixed> */
    public function jsonSchema(): array
    {
        return ['type' => 'number'];
    }
}
