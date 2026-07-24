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
use StdOut\SimpleDataObjects\Support\HydratorCompiler;
use StdOut\SimpleDataObjects\Support\InputNormalizer;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Support\SerializerCompiler;
use StdOut\SimpleDataObjects\Support\ValueCaster;
use Stringable;

abstract class BaseData implements Arrayable, DataObject, JsonSerializable, Stringable
{
    private static ?ValidatorFactory $validatorFactory = null;

    /** @var array<class-string, \ReflectionClass<BaseData>> */
    private static array $reflectors = [];

    /**
     * Universal factory: accepts an array, an Eloquent model or any
     * Arrayable, stdClass, JsonSerializable, any Traversable, a JSON string,
     * a plain object (public properties), or an existing instance of the
     * same class (returned as-is — instances are immutable).
     */
    public static function from(mixed $data): static
    {
        $hydrate = HydratorCompiler::$hydrators[static::class] ?? HydratorCompiler::compile(static::class);

        if (is_array($data)) {
            /** @var static */
            return $hydrate($data);
        }

        if ($data instanceof static) {
            return $data;
        }

        /** @var static */
        return $hydrate(InputNormalizer::normalize(static::class, $data));
    }

    /**
     * Returns an uninitialized lazy ghost (PHP 8.4): hydration cost is
     * deferred until the first property access. Use when many DTOs are
     * created but only some are ever read. Note that invalid input therefore
     * throws on first access, not here.
     */
    public static function fromLazy(mixed $data): static
    {
        $class = static::class;
        $reflector = self::$reflectors[$class] ??= new \ReflectionClass($class);

        /** @var static */
        return $reflector->newLazyGhost(static function (object $ghost) use ($class, $data): void {
            $normalized = is_array($data) ? $data : InputNormalizer::normalize($class, $data);

            // Kept inside the initializer (not hoisted into fromLazy()) so a
            // cold metadata cache is only ever built on first property access.
            $meta = MetadataRegistry::get($class);

            if (! $meta->hasConstructor) {
                (HydratorCompiler::$populators[$class] ?? HydratorCompiler::compilePopulate($class))($normalized, $ghost);

                return;
            }

            $args = (HydratorCompiler::$argResolvers[$class] ?? HydratorCompiler::compileArgs($class))($normalized);
            $ghost->__construct(...$args);

            // Hybrid: the constructor only covers its own parameters — the
            // extra properties still need populating, same mechanism as the
            // constructor-less path above.
            if ($meta->hasExtraProperties) {
                (HydratorCompiler::$populators[$class] ?? HydratorCompiler::compilePopulate($class))($normalized, $ghost);
            }
        });
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
            $hydrate = HydratorCompiler::$hydrators[$class] ?? HydratorCompiler::compile($class);

            foreach ($items as $item) {
                yield $item instanceof $class
                    ? $item
                    : $hydrate(is_array($item) ? $item : InputNormalizer::normalize($class, $item));
            }
        });
    }

    /** Explicit alias of from() for JSON input — same decoding and errors. */
    public static function fromJson(string $json): static
    {
        return static::from($json);
    }

    public function with(mixed ...$overrides): static
    {
        $meta = MetadataRegistry::get(static::class);
        $current = get_object_vars($this);
        $ctorArgs = [];
        $extra = [];

        foreach ($meta->parameters as $param) {
            if (array_key_exists($param->phpName, $overrides)) {
                $value = $overrides[$param->phpName];
                $value = $param->isPlain ? $value : ValueCaster::cast($param, $value);
                unset($overrides[$param->phpName]);
            } else {
                $value = $current[$param->phpName];
            }

            if ($param->viaConstructor) {
                $ctorArgs[] = $value;
            } else {
                $extra[$param->phpName] = $value;
            }
        }

        if ($overrides !== []) {
            throw new \InvalidArgumentException(
                sprintf('Unknown propert%s [%s] for %s::with().', count($overrides) === 1 ? 'y' : 'ies', implode(', ', array_keys($overrides)), static::class),
            );
        }

        $instance = $meta->hasConstructor ? new static(...$ctorArgs) : new static;

        // Extra (non-constructor) properties — constructor-less classes and
        // hybrid classes' extra fields. Legal even for readonly properties:
        // the write happens from BaseData's own scope, an ancestor of every
        // subclass, which PHP treats as within the declaring scope.
        foreach ($extra as $phpName => $value) {
            $instance->{$phpName} = $value;
        }

        return $instance;
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
