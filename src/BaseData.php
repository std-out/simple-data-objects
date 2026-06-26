<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;
use StdOut\SimpleDataObjects\Contracts\DataObject;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\Hydrator;
use Stringable;

abstract class BaseData implements Arrayable, DataObject, JsonSerializable, Stringable
{
    public static function from(mixed $data): static
    {
        return new static(...Hydrator::resolveArguments(static::class, $data));
    }

    public static function collection(iterable $items): TypedDataCollection
    {
        $class = static::class;

        return new TypedDataCollection(
            collect($items)->map(fn (mixed $item) => $item instanceof $class ? $item : $class::from($item)),
        );
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw DataHydrationException::invalidJson(static::class);
        }

        return static::from($data);
    }

    public function toArray(): array
    {
        $meta = Hydrator::classMeta(static::class);
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if (isset($meta->hidden[$key])) {
                continue;
            }

            if (is_null($value) && isset($meta->ignoreIfNull[$key])) {
                continue;
            }

            $result[$key] = isset($meta->casters[$key])
                ? $meta->casters[$key]->set($value)
                : $this->normalizeValue($value);
        }

        return $result;
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags) ?: '{}';
    }

    public function only(string ...$keys): array
    {
        return array_intersect_key($this->toArray(), array_flip($keys));
    }

    public function except(string ...$keys): array
    {
        return array_diff_key($this->toArray(), array_flip($keys));
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if ($value instanceof Collection) {
            return $value->map(fn (mixed $item): mixed => $this->normalizeValue($item))->all();
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }
}
