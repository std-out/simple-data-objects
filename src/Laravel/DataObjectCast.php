<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use StdOut\SimpleDataObjects\BaseData;

/**
 * @implements CastsAttributes<BaseData, BaseData|array|string>
 */
final class DataObjectCast implements CastsAttributes, ComparesCastableAttributes
{
    /** @param class-string<BaseData> $dataClass */
    public function __construct(private readonly string $dataClass) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?BaseData
    {
        return $value === null ? null : $this->dataClass::from($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : $this->dataClass::from($value)->toJson();
    }

    /**
     * Without this, Eloquent's dirty-checking falls back to comparing the
     * raw stored JSON strings byte-for-byte, so a freshly re-serialized but
     * unchanged value can appear dirty. Decoding and comparing via equals()
     * fixes that.
     */
    public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
    {
        if ($firstValue === $secondValue) {
            return true;
        }

        if ($firstValue === null || $secondValue === null) {
            return false;
        }

        return $this->dataClass::from($firstValue)->equals($this->dataClass::from($secondValue));
    }
}
