<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class TrimCast implements CastsValue
{
    public const string LOWERCASE = 'lowercase';

    public const string UPPERCASE = 'uppercase';

    public function __construct(
        private readonly ?string $transform = null,
    ) {}

    public static function __set_state(array $state): self
    {
        return new self($state['transform']);
    }

    public function get(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);

        return match ($this->transform) {
            self::LOWERCASE => strtolower($str),
            self::UPPERCASE => strtoupper($str),
            default => $str,
        };
    }

    public function set(mixed $value): ?string
    {
        return $this->get($value);
    }
}
