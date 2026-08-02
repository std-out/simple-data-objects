<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use Illuminate\Validation\Rule;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;

/** #[InferRules] support — derives rules from a parameter's PHP type. */
final class RuleInferrer
{
    /**
     * Classes mid-build, so cascading into a self-referential or cyclic
     * BaseData graph can detect the cycle instead of recursing.
     *
     * @var list<class-string>
     */
    private static array $inProgress = [];

    /**
     * @template T
     *
     * @param  callable(): T  $build
     * @return T
     */
    public static function guarding(string $class, callable $build): mixed
    {
        self::$inProgress[] = $class;

        try {
            return $build();
        } finally {
            array_pop(self::$inProgress);
        }
    }

    /** @return list<mixed> */
    public static function forParameter(
        ReflectionParameter|ReflectionProperty $parameter,
        bool $allowsNull,
        ?string $nestedDataClass,
        ?string $enumClass,
        ?string $dataCollectionClass,
    ): array {
        $presence = $allowsNull ? 'nullable' : 'required';

        if ($enumClass !== null) {
            return [$presence, Rule::enum($enumClass)];
        }

        if ($nestedDataClass !== null || $dataCollectionClass !== null) {
            return [$presence, 'array'];
        }

        $scalarRule = self::scalarRule($parameter);

        return $scalarRule !== null ? [$presence, $scalarRule] : [$presence];
    }

    /** @return array<string, array<mixed>> */
    public static function cascade(?string $nestedDataClass, ?string $dataCollectionClass): array
    {
        if ($nestedDataClass !== null) {
            return self::nestedClassRules($nestedDataClass);
        }

        if ($dataCollectionClass !== null) {
            $rules = [];

            foreach (self::nestedClassRules($dataCollectionClass) as $suffix => $rule) {
                $rules["*.{$suffix}"] = $rule;
            }

            return $rules;
        }

        return [];
    }

    private static function scalarRule(ReflectionParameter|ReflectionProperty $parameter): ?string
    {
        $type = $parameter->getType();

        $name = match (true) {
            $type instanceof ReflectionNamedType => $type->getName(),
            $type instanceof ReflectionUnionType => self::firstBuiltinUnionMember($type),
            default => null,
        };

        return match ($name) {
            'int' => 'integer',
            'float' => 'numeric',
            'string' => 'string',
            'bool' => 'boolean',
            'array' => 'array',
            default => null,
        };
    }

    private static function firstBuiltinUnionMember(ReflectionUnionType $type): ?string
    {
        foreach ($type->getTypes() as $sub) {
            if ($sub instanceof ReflectionNamedType && $sub->isBuiltin() && $sub->getName() !== 'null') {
                return $sub->getName();
            }
        }

        return null;
    }

    /** @return array<string, array<mixed>> */
    private static function nestedClassRules(string $dataClass): array
    {
        if (in_array($dataClass, self::$inProgress, true)) {
            return [];
        }

        return MetadataRegistry::get($dataClass)->validationRules;
    }
}
