<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\LazyCollection;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use JsonSerializable;
use StdOut\SimpleDataObjects\Contracts\DataObject;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\HydratorCompiler;
use StdOut\SimpleDataObjects\Support\InputNormalizer;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Support\SerializerCompiler;
use StdOut\SimpleDataObjects\Support\ValueCaster;
use Stringable;

abstract class BaseData implements Arrayable, DataObject, JsonSerializable, Stringable
{
    private static ?ValidatorFactory $validatorFactory = null;

    public static function from(mixed $data): static
    {
        /** @var static */
        return (HydratorCompiler::$hydrators[static::class] ?? HydratorCompiler::compile(static::class))(
            is_array($data) ? $data : InputNormalizer::normalize(static::class, $data),
        );
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
        return TypedDataCollection::of(static::class, $items);
    }

    /**
     * Like `collection()`, but hydrates one item at a time as the collection
     * is consumed instead of materializing the whole array upfront. Use this
     * for large iterables (a DB cursor, a generator reading a big CSV) where
     * holding every hydrated instance in memory at once would be wasteful.
     *
     * @return LazyCollection<int, static>
     */
    public static function lazyCollection(iterable $items): LazyCollection
    {
        $class = static::class;

        return LazyCollection::make(static function () use ($items, $class): \Generator {
            foreach ($items as $item) {
                yield $item instanceof $class ? $item : $class::from($item);
            }
        });
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true, 32);

        if (! is_array($data)) {
            throw DataHydrationException::invalidJson(static::class);
        }

        return static::from($data);
    }

    public function with(mixed ...$overrides): static
    {
        $meta = MetadataRegistry::get(static::class);
        $current = get_object_vars($this);
        $args = [];

        foreach ($meta->parameters as $param) {
            if (array_key_exists($param->phpName, $overrides)) {
                $value = $overrides[$param->phpName];
                $args[] = $param->isPlain ? $value : ValueCaster::cast($param, $value);
                unset($overrides[$param->phpName]);

                continue;
            }

            $args[] = $current[$param->phpName];
        }

        if ($overrides !== []) {
            throw new \InvalidArgumentException(
                sprintf('Unknown propert%s [%s] for %s::with().', count($overrides) === 1 ? 'y' : 'ies', implode(', ', array_keys($overrides)), static::class),
            );
        }

        return new static(...$args);
    }

    public static function fromValidated(mixed $data): static
    {
        $array = is_array($data) ? $data : InputNormalizer::normalize(static::class, $data);
        $meta = MetadataRegistry::get(static::class);

        if ($meta->validationRules !== []) {
            static::validatorFactory()->make($array, $meta->validationRules)->validate();
        }

        return static::from($array);
    }

    /** @throws ValidationException */
    public static function validate(mixed $data): void
    {
        $meta = MetadataRegistry::get(static::class);

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
            // Not memoized: long-running runtimes (Octane) may rebind the
            // container between requests; resolving a singleton is cheap.
            /** @var ValidatorFactory $factory */
            $factory = $container->make('validator');

            return $factory;
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
        return $other::class === static::class && $this->toArray() === $other->toArray();
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
        return (SerializerCompiler::$serializers[static::class] ?? SerializerCompiler::compile(static::class))($this);
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
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
}
