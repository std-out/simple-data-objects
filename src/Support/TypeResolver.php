<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use StdOut\SimpleDataObjects\Contracts\DataObject;
use StdOut\SimpleDataObjects\Optional;

final class TypeResolver
{
    /** @return array{0: ?string, 1: ?string, 2: bool} nested class, enum class, isOptional */
    public static function resolve(ReflectionParameter|ReflectionProperty $parameter): array
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return [...self::classEntry($type->getName()), $type->getName() === Optional::class];
        }

        if ($type instanceof ReflectionUnionType) {
            $nested = null;
            $enum = null;
            $isOptional = false;

            foreach ($type->getTypes() as $sub) {
                if (! $sub instanceof ReflectionNamedType || $sub->isBuiltin() || $sub->getName() === 'null') {
                    continue;
                }

                if ($sub->getName() === Optional::class) {
                    $isOptional = true;

                    continue;
                }

                if ($nested === null && $enum === null) {
                    [$nested, $enum] = self::classEntry($sub->getName());
                }
            }

            return [$nested, $enum, $isOptional];
        }

        return [null, null, false];
    }

    private static function classEntry(string $typeName): array
    {
        if (is_subclass_of($typeName, DataObject::class)) {
            return [$typeName, null];
        }

        if (enum_exists($typeName)) {
            return [null, $typeName];
        }

        return [null, null];
    }
}
