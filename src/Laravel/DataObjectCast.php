<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;

/**
 * @implements CastsAttributes<BaseData, BaseData|array<mixed>|string>
 */
final class DataObjectCast implements CastsAttributes
{
    /** @param class-string<BaseData> $dataClass */
    public function __construct(
        private readonly string $dataClass,
        private readonly ?string $context = null,
    ) {}

    public function get(Model $model, string $key, #[\SensitiveParameter] mixed $value, #[\SensitiveParameter] array $attributes): ?BaseData
    {
        return $value === null ? null : $this->dataClass::from($value);
    }

    public function set(Model $model, string $key, #[\SensitiveParameter] mixed $value, #[\SensitiveParameter] array $attributes): ?string
    {
        return $value === null ? null : $this->dataClass::from($value)->toJson(context: $this->context);
    }

    /**
     * Picked up by Eloquent via method_exists(), not an interface — Laravel
     * 12+ only. `ComparesCastableAttributes` isn't declared here since it
     * doesn't exist before Laravel 12.
     */
    public function compare(Model $model, string $key, #[\SensitiveParameter] mixed $firstValue, #[\SensitiveParameter] mixed $secondValue): bool
    {
        if ($firstValue === $secondValue) {
            return true;
        }

        if ($firstValue === null || $secondValue === null) {
            return false;
        }

        $first = $this->dataClass::from($firstValue);
        $second = $this->dataClass::from($secondValue);

        return $first::class === $second::class
            && EncryptedCast::sameIgnoringNonces(fn (): bool => $first->toArray($this->context) === $second->toArray($this->context));
    }
}
