<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;

final class Hydrator
{
    public static function resolveArguments(string $class, mixed $input): array
    {
        $data = InputNormalizer::normalize($class, $input);
        $meta = MetadataRegistry::get($class);
        $arguments = [];

        foreach ($meta->parameters as $param) {
            if (array_key_exists($param->inputName, $data)) {
                $arguments[] = ValueCaster::cast($param, $data[$param->inputName]);

                continue;
            }

            if ($param->hasDefault) {
                $arguments[] = $param->defaultValue;

                continue;
            }

            if ($param->allowsNull) {
                $arguments[] = null;

                continue;
            }

            throw DataHydrationException::missingField($class, $param->inputName);
        }

        return $arguments;
    }

    public static function classMeta(string $class): ClassMeta
    {
        return MetadataRegistry::get($class);
    }
}
