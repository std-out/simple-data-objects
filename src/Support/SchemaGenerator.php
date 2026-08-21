<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use BackedEnum;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;

/**
 * Walks ClassMeta/ParameterMeta (no reflection) into a JSON Schema (draft
 * 2020-12) describing a class's toArray()/toJson() output shape.
 */
final class SchemaGenerator
{
    /** @param class-string $class */
    public static function generate(string $class): array
    {
        $defs = [];
        $schema = self::classSchema($class, $defs);

        return $defs === [] ? $schema : ['$defs' => $defs, ...$schema];
    }

    /** @param array<string, array> $defs */
    private static function classSchema(string $class, array &$defs): array
    {
        $meta = MetadataRegistry::get($class);

        if ($meta->discriminatorField !== null) {
            return self::discriminatorSchema($meta, $defs);
        }

        [$properties, $required] = self::properties($meta, $defs);

        foreach ($meta->computed as $key) {
            $properties[$key] = [];
        }

        $schema = ['type' => 'object', 'properties' => $properties];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /** @return array{0: array<string, array>, 1: list<string>} */
    private static function properties(ClassMeta $meta, array &$defs): array
    {
        $properties = [];
        $required = [];

        foreach ($meta->parameters as $param) {
            if ($param->isHidden) {
                continue;
            }

            if ($param->flatten) {
                [$nestedProperties, $nestedRequired] = self::properties(MetadataRegistry::get($param->nestedDataClass), $defs);
                $properties = [...$properties, ...$nestedProperties];
                $required = [...$required, ...$nestedRequired];

                continue;
            }

            $properties[$param->outputName] = self::fieldSchema($param, $defs);

            if (! $param->allowsNull && ! $param->hasDefault && ! $param->isOptional) {
                $required[] = $param->outputName;
            }
        }

        return [$properties, array_values(array_unique($required))];
    }

    /**
     * Per-field schema, shared with TypeScriptGenerator so type resolution
     * (casts, enums, nested refs, collections, rules) exists in one place.
     *
     * @param  array<string, array>  $defs
     */
    public static function fieldSchema(ParameterMeta $param, array &$defs): array
    {
        $schema = match (true) {
            $param->caster instanceof ProvidesJsonSchema => $param->caster->jsonSchema(),
            $param->enumClass !== null => self::enumSchema($param->enumClass),
            $param->nestedDataClass !== null => self::refSchema($param->nestedDataClass, $defs),
            $param->dataCollectionClass !== null => ['type' => 'array', 'items' => self::refSchema($param->dataCollectionClass, $defs)],
            $param->phpType !== null => self::scalarSchema($param->phpType),
            default => [],
        };

        if ($param->rules !== []) {
            $schema = self::applyRules($schema, $param->rules);
        }

        if ($param->allowsNull && isset($schema['type'])) {
            $schema['type'] = [...(array) $schema['type'], 'null'];
        }

        return $schema;
    }

    private static function scalarSchema(string $phpType): array
    {
        return match ($phpType) {
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number'],
            'string' => ['type' => 'string'],
            'bool' => ['type' => 'boolean'],
            'array' => ['type' => 'array'],
            default => [],
        };
    }

    /** @param class-string $enumClass */
    private static function enumSchema(string $enumClass): array
    {
        $isBacked = is_subclass_of($enumClass, BackedEnum::class);
        $values = array_map(
            static fn (object $case): int|string => $isBacked ? $case->value : $case->name,
            $enumClass::cases(),
        );

        return ['type' => is_int($values[0] ?? null) ? 'integer' : 'string', 'enum' => $values];
    }

    /** @param array<string, array> $defs */
    private static function refSchema(string $class, array &$defs): array
    {
        $key = self::defKey($class);

        if (! array_key_exists($key, $defs)) {
            $defs[$key] = [];
            $defs[$key] = self::classSchema($class, $defs);
        }

        return ['$ref' => "#/\$defs/{$key}"];
    }

    /** @param class-string $class */
    public static function defKey(string $class): string
    {
        $pos = strrpos($class, '\\');

        return $pos === false ? $class : substr($class, $pos + 1);
    }

    /** @param array<string, array> $defs */
    private static function discriminatorSchema(ClassMeta $meta, array &$defs): array
    {
        $classes = $meta->discriminatorMap;

        if ($meta->discriminatorFallback !== null) {
            $classes[] = $meta->discriminatorFallback;
        }

        $refs = [];

        foreach (array_unique($classes) as $childClass) {
            $refs[] = self::refSchema($childClass, $defs);
        }

        return ['oneOf' => $refs];
    }

    private static function applyRules(array $schema, array $rules): array
    {
        $type = $schema['type'] ?? null;

        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                continue;
            }

            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

            $schema = match (true) {
                $name === 'email' => [...$schema, 'format' => 'email'],
                $name === 'url' => [...$schema, 'format' => 'uri'],
                $name === 'uuid' => [...$schema, 'format' => 'uuid'],
                $name === 'in' && $arg !== null => [...$schema, 'enum' => explode(',', $arg)],
                $name === 'max' && $arg !== null => self::applyBound($schema, $type, 'max', (float) $arg),
                $name === 'min' && $arg !== null => self::applyBound($schema, $type, 'min', (float) $arg),
                $name === 'size' && $arg !== null => self::applyBound(self::applyBound($schema, $type, 'max', (float) $arg), $type, 'min', (float) $arg),
                default => $schema,
            };
        }

        return $schema;
    }

    private static function applyBound(array $schema, ?string $type, string $bound, float $value): array
    {
        $key = match (true) {
            $type === 'string' => $bound === 'max' ? 'maxLength' : 'minLength',
            $type === 'array' => $bound === 'max' ? 'maxItems' : 'minItems',
            $type === 'integer' || $type === 'number' => $bound === 'max' ? 'maximum' : 'minimum',
            default => null,
        };

        if ($key === null) {
            return $schema;
        }

        $schema[$key] = str_ends_with((string) $key, 'Length') || str_ends_with((string) $key, 'Items') ? (int) $value : $value;

        return $schema;
    }
}
