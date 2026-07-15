<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class UuidCast implements CastsValue
{
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public static function __set_state(array $state): self
    {
        return new self;
    }

    public function get(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $uuid = (string) $value;

        if (! preg_match(self::PATTERN, $uuid)) {
            throw new InvalidArgumentException("\"{$uuid}\" is not a valid RFC 4122 UUID.");
        }

        return strtolower($uuid);
    }

    public function set(mixed $value): ?string
    {
        return $this->get($value);
    }
}
