<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use BackedEnum;
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use UnitEnum;

final class EnumCast implements CastsValue
{
    public function __construct(
        private readonly string $enumClass,
        private readonly ?UnitEnum $default = null,
    ) {}

    public function get(mixed $value): ?UnitEnum
    {
        if ($value === null) {
            return $this->default;
        }

        if ($value instanceof $this->enumClass) {
            return $value;
        }

        if (is_subclass_of($this->enumClass, BackedEnum::class)) {
            return $this->enumClass::tryFrom($value) ?? $this->default;
        }

        return $this->default;
    }

    public function set(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $value;
    }
}
