<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class CommaSeparatedCast implements CastsValue
{
    public function __construct(
        private readonly string $separator = ',',
        private readonly bool $trim = true,
    ) {}

    public static function __set_state(array $state): self
    {
        return new self($state['separator'], $state['trim']);
    }

    public function get(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $str = (string) $value;

        if ($str === '') {
            return [];
        }

        $parts = explode($this->separator, $str);

        return $this->trim ? array_map('trim', $parts) : $parts;
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return implode($this->separator, $value);
    }
}
