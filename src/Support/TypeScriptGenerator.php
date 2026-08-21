<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

/**
 * Maps SchemaGenerator's per-field JSON Schema fragments to TypeScript —
 * one `export interface` per concrete class, one `export type` union per
 * #[Discriminator] parent. Field optionality (`?:`) follows Optional (1.7),
 * not the JSON Schema "required" list: unlike hydration-required, a
 * nullable-but-always-present field is never omitted from toArray() output,
 * so it stays a required TS key typed `| null`.
 */
final class TypeScriptGenerator
{
    /** @param list<class-string> $classes */
    public static function generate(array $classes): string
    {
        $output = '';

        foreach ($classes as $class) {
            $meta = MetadataRegistry::get($class);

            $output .= $meta->discriminatorField !== null
                ? self::typeAlias($class, $meta)
                : self::interfaceFor($class, $meta);
        }

        return $output;
    }

    private static function interfaceFor(string $class, ClassMeta $meta): string
    {
        $defs = [];
        $lines = [];

        foreach (self::fields($meta, $defs) as $name => [$schema, $optional]) {
            $lines[] = "  {$name}".($optional ? '?' : '').': '.self::tsType($schema).';';
        }

        foreach ($meta->computed as $key) {
            $lines[] = "  {$key}: unknown;";
        }

        $body = implode("\n", $lines);

        return 'export interface '.SchemaGenerator::defKey($class)." {\n{$body}\n}\n\n";
    }

    /**
     * @param  array<string, array>  $defs
     * @return array<string, array{0: array, 1: bool}> output name => [schema, isOptional]
     */
    private static function fields(ClassMeta $meta, array &$defs): array
    {
        $fields = [];

        foreach ($meta->parameters as $param) {
            if ($param->isHidden) {
                continue;
            }

            if ($param->flatten) {
                $fields = [...$fields, ...self::fields(MetadataRegistry::get($param->nestedDataClass), $defs)];

                continue;
            }

            $fields[$param->outputName] = [SchemaGenerator::fieldSchema($param, $defs), $param->isOptional];
        }

        return $fields;
    }

    private static function typeAlias(string $class, ClassMeta $meta): string
    {
        $classes = $meta->discriminatorMap;

        if ($meta->discriminatorFallback !== null) {
            $classes[] = $meta->discriminatorFallback;
        }

        $names = array_map(SchemaGenerator::defKey(...), array_values(array_unique($classes)));

        return 'export type '.SchemaGenerator::defKey($class).' = '.implode(' | ', $names).";\n\n";
    }

    private static function tsType(array $schema): string
    {
        $type = $schema['type'] ?? null;

        if (is_array($type) && in_array('null', $type, true)) {
            $inner = $schema;
            $inner['type'] = array_values(array_filter($type, static fn (string $t): bool => $t !== 'null'))[0] ?? null;

            return self::tsType($inner).' | null';
        }

        if (isset($schema['enum'])) {
            return implode(' | ', array_map(
                static fn (int|string $v): string => is_string($v) ? "'".addslashes($v)."'" : (string) $v,
                $schema['enum'],
            ));
        }

        if (isset($schema['$ref'])) {
            $pos = strrpos((string) $schema['$ref'], '/');

            return $pos === false ? $schema['$ref'] : substr((string) $schema['$ref'], $pos + 1);
        }

        if ($type === 'array' && isset($schema['items'])) {
            return self::tsType($schema['items']).'[]';
        }

        return match ($type) {
            'integer', 'number' => 'number',
            'string' => 'string',
            'boolean' => 'boolean',
            'array' => 'unknown[]',
            default => 'unknown',
        };
    }
}
