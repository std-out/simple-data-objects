<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

use BackedEnum;
use Illuminate\Container\Container;
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
use StdOut\SimpleDataObjects\Support\ValueCaster;
use Stringable;

abstract class BaseData implements Arrayable, DataObject, JsonSerializable, Stringable
{
    private static ?ValidatorFactory $validatorFactory = null;

    public static function from(mixed $data): static
    {
        return new static(...Hydrator::resolveArguments(static::class, $data));
    }

    public static function tryFrom(mixed $data): ?static
    {
        try {
            return static::from($data);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return TypedDataCollection<static>
     */
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

    public function with(mixed ...$overrides): static
    {
        $meta = Hydrator::classMeta(static::class);
        $current = get_object_vars($this);
        $args = [];

        foreach ($meta->parameters as $param) {
            $args[] = array_key_exists($param->phpName, $overrides)
                ? ValueCaster::cast($param, $overrides[$param->phpName])
                : $current[$param->phpName];
        }

        return new static(...$args);
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

        $container = Container::getInstance();
        if ($container->bound('validator')) {
            /** @var ValidatorFactory $factory */
            $factory = $container->make('validator');

            return self::$validatorFactory = $factory;
        }

        return self::$validatorFactory = new ValidatorFactory(
            new Translator(
                new ArrayLoader,
                'en',
            ),
        );
    }

    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /** @return array<string, array{0: mixed, 1: mixed}> */
    public function diff(self $other): array
    {
        $a = $this->toArray();
        $b = $other->toArray();
        $result = [];

        foreach (array_keys($a + $b) as $key) {
            $aVal = $a[$key] ?? null;
            $bVal = $b[$key] ?? null;

            if ($aVal !== $bVal) {
                $result[$key] = [$aVal, $bVal];
            }
        }

        return $result;
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

            if (isset($meta->flattened[$key]) && $value instanceof self) {
                $result = array_merge($result, $value->toArray());

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
