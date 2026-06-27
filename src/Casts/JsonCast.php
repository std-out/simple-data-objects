<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class JsonCast implements CastsValue
{
    public function __construct(
        private readonly bool $assoc = true,
        private readonly int $encodeFlags = 0,
    ) {}

    public static function __set_state(array $state): self
    {
        return new self($state['assoc'], $state['encodeFlags']);
    }

    public function get(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return $value;
        }

        return json_decode((string) $value, $this->assoc, 64, JSON_THROW_ON_ERROR);
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, $this->encodeFlags | JSON_THROW_ON_ERROR);
    }
}
