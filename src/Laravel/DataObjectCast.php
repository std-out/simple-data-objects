<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use StdOut\SimpleDataObjects\BaseData;

/**
 * @implements CastsAttributes<BaseData, BaseData|array|string>
 */
final class DataObjectCast implements CastsAttributes
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
     * Picked up by Eloquent via method_exists(), not an interface — Laravel
     * 12+ only. `ComparesCastableAttributes` isn't declared here since it
     * doesn't exist before Laravel 12.
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
