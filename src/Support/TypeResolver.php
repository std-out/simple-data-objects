<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use StdOut\SimpleDataObjects\Contracts\DataObject;

final class TypeResolver
{
    public static function resolve(ReflectionParameter|ReflectionProperty $parameter): array
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return self::classEntry($type->getName());
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $sub) {
                if ($sub instanceof ReflectionNamedType && ! $sub->isBuiltin() && $sub->getName() !== 'null') {
                    [$nested, $enum] = self::classEntry($sub->getName());

                    if ($nested !== null || $enum !== null) {
                        return [$nested, $enum];
                    }
                }
            }
        }

        return [null, null];
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
