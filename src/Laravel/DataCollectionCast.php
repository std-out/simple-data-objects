<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Laravel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\TypedDataCollection;

/**
 * @implements CastsAttributes<TypedDataCollection, TypedDataCollection|iterable|string>
 */
final class DataCollectionCast implements CastsAttributes
{
    /** @param class-string<BaseData> $dataClass */
    public function __construct(private readonly string $dataClass) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?TypedDataCollection
    {
        if ($value === null) {
            return null;
        }

        $items = $value;

        if (is_string($items)) {
            $decoded = json_decode($items, true, 32);

            if (! is_array($decoded)) {
                throw DataHydrationException::invalidJson($this->dataClass);
            }

            $items = $decoded;
        }

        return $this->dataClass::collection($items);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $collection = $value instanceof TypedDataCollection ? $value : $this->dataClass::collection($value);

        return $collection->toJson(JSON_THROW_ON_ERROR);
    }

    /** See DataObjectCast::compare() for why this exists. */
    public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
    {
        if ($firstValue === $secondValue) {
            return true;
        }

        if ($firstValue === null || $secondValue === null) {
            return false;
        }

        $toArrays = static fn (TypedDataCollection $items): array => $items
            ->map(static fn (BaseData $item): array => $item->toArray())
            ->all();

        return $toArrays($this->get($model, $key, $firstValue, []))
            === $toArrays($this->get($model, $key, $secondValue, []));
    }
}
