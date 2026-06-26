<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use StdOut\SimpleDataObjects\Attributes\DataCollection as DataCollectionAttribute;
use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\Attributes\TransformKeys;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use Stringable;
use UnitEnum;

abstract class BaseData implements Arrayable, JsonSerializable, Stringable
{
    private static array $reflectionCache = [];

    private static array $metaCache = [];

    private static array $hiddenCache = [];

    public static function from(mixed $data): static
    {
        $data = self::normalizeInput($data);
        $arguments = [];

        foreach (self::getMetadata(static::class) as $param) {
            if (array_key_exists($param['inputName'], $data)) {
                $arguments[] = self::castValue($param, $data[$param['inputName']]);

                continue;
            }

            if ($param['hasDefault']) {
                $arguments[] = $param['defaultValue'];

                continue;
            }

            if ($param['allowsNull']) {
                $arguments[] = null;

                continue;
            }

            throw DataHydrationException::missingField(static::class, $param['inputName']);
        }

        return new static(...$arguments);
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
        $hidden = self::getHiddenSet(static::class);
        $ignoreIfNull = array_flip($this->ignoreIfNull());
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if (isset($hidden[$key])) {
                continue;
            }

            if (is_null($value) && isset($ignoreIfNull[$key])) {
                continue;
            }

            $result[$key] = $this->normalizeValue($value);
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

    protected function ignoreIfNull(): array
    {
        return [];
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

    private static function getMetadata(string $class): array
    {
        if (isset(self::$metaCache[$class])) {
            return self::$metaCache[$class];
        }

        $constructor = self::getReflection($class)->getConstructor();

        if ($constructor === null) {
            return self::$metaCache[$class] = [];
        }

        $transformAttrs = self::getReflection($class)->getAttributes(TransformKeys::class);
        $strategy = $transformAttrs !== [] ? $transformAttrs[0]->newInstance()->strategy : null;

        return self::$metaCache[$class] = array_map(
            static fn (ReflectionParameter $p): array => self::buildMeta($p, $strategy),
            $constructor->getParameters(),
        );
    }

    private static function getHiddenSet(string $class): array
    {
        if (isset(self::$hiddenCache[$class])) {
            return self::$hiddenCache[$class];
        }

        $hidden = [];

        foreach (self::getMetadata($class) as $param) {
            if ($param['isHidden']) {
                $hidden[$param['phpName']] = true;
            }
        }

        return self::$hiddenCache[$class] = $hidden;
    }

    private static function getReflection(string $class): ReflectionClass
    {
        return self::$reflectionCache[$class] ??= new ReflectionClass($class);
    }

    private static function buildMeta(ReflectionParameter $parameter, ?string $strategy = null): array
    {
        $phpName = $parameter->getName();
        $hasDefault = $parameter->isDefaultValueAvailable();

        $mapAttrs = $parameter->getAttributes(MapPropertyName::class);
        $inputName = match (true) {
            $mapAttrs !== [] => (string) $mapAttrs[0]->newInstance()->input,
            $strategy !== null => self::applyKeyStrategy($phpName, $strategy),
            default => $phpName,
        };

        $collectionAttrs = $parameter->getAttributes(DataCollectionAttribute::class);
        $dataCollectionClass = null;

        if ($collectionAttrs !== []) {
            $dataCollectionClass = $collectionAttrs[0]->newInstance()->dataClass;

            if (! class_exists($dataCollectionClass)) {
                throw DataHydrationException::classNotFound($dataCollectionClass);
            }
        }

        [$nestedDataClass, $enumClass] = self::resolveType($parameter);

        return [
            'phpName' => $phpName,
            'inputName' => $inputName,
            'allowsNull' => $parameter->allowsNull(),
            'hasDefault' => $hasDefault,
            'defaultValue' => $hasDefault ? $parameter->getDefaultValue() : null,
            'nestedDataClass' => $nestedDataClass,
            'enumClass' => $enumClass,
            'dataCollectionClass' => $dataCollectionClass,
            'isHidden' => $parameter->getAttributes(Hidden::class) !== [],
        ];
    }

    private static function resolveType(ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return self::classTypeEntry($type->getName());
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $sub) {
                if ($sub instanceof ReflectionNamedType && ! $sub->isBuiltin() && $sub->getName() !== 'null') {
                    [$nested, $enum] = self::classTypeEntry($sub->getName());

                    if ($nested !== null || $enum !== null) {
                        return [$nested, $enum];
                    }
                }
            }
        }

        return [null, null];
    }

    private static function classTypeEntry(string $typeName): array
    {
        if (is_subclass_of($typeName, self::class)) {
            return [$typeName, null];
        }

        if (enum_exists($typeName)) {
            return [null, $typeName];
        }

        return [null, null];
    }

    private static function applyKeyStrategy(string $phpName, string $strategy): string
    {
        return match ($strategy) {
            TransformKeys::SNAKE_CASE => strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $phpName)),
            TransformKeys::CAMEL_CASE => lcfirst(str_replace('_', '', ucwords($phpName, '_'))),
            default => $phpName,
        };
    }

    private static function castValue(array $param, mixed $value): mixed
    {
        if ($param['dataCollectionClass'] !== null) {
            if (is_null($value)) {
                return $param['allowsNull'] ? null : new TypedDataCollection;
            }

            return $param['dataCollectionClass']::collection($value);
        }

        if ($param['nestedDataClass'] !== null && ! is_null($value)) {
            return $param['nestedDataClass']::from($value);
        }

        if ($param['enumClass'] !== null && ! is_null($value) && ! ($value instanceof UnitEnum)) {
            return $param['enumClass']::tryFrom($value);
        }

        return $value;
    }

    private static function normalizeInput(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if ($data instanceof \stdClass) {
            return (array) $data;
        }

        if ($data instanceof JsonSerializable) {
            $result = $data->jsonSerialize();

            return is_array($result) ? $result : (array) $result;
        }

        throw DataHydrationException::invalidInput(static::class, get_debug_type($data));
    }
}
