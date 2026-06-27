<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use JsonSerializable;
use StdOut\SimpleDataObjects\Contracts\DataObject;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\Hydrator;
use StdOut\SimpleDataObjects\Support\InputNormalizer;
use Stringable;

abstract class BaseData implements Arrayable, DataObject, JsonSerializable, Stringable
{
    private static ?ValidatorFactory $validatorFactory = null;

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

    public static function fromValidated(mixed $data): static
    {
        static::validate($data);

        return static::from($data);
    }

    /** @throws ValidationException */
    public static function validate(mixed $data): void
    {
        $meta = Hydrator::classMeta(static::class);

        if ($meta->validationRules === []) {
            return;
        }

        $array = is_array($data) ? $data : InputNormalizer::normalize(static::class, $data);

        static::validatorFactory()
            ->make($array, $meta->validationRules)
            ->validate();
    }

    public static function setValidatorFactory(ValidatorFactory $factory): void
    {
        self::$validatorFactory = $factory;
    }

    private static function validatorFactory(): ValidatorFactory
    {
        if (self::$validatorFactory !== null) {
            return self::$validatorFactory;
        }

        if (function_exists('app') && app()->bound('validator')) {
            return self::$validatorFactory = app('validator');
        }

        return self::$validatorFactory = new ValidatorFactory(
            new Translator(
                new ArrayLoader,
                'en',
            ),
        );
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
