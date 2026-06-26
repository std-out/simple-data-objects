<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

final class MetadataRegistry
{
    /** @var array<string, ClassMeta> */
    private static array $cache = [];

    public static function get(string $class): ClassMeta
    {
        return self::$cache[$class] ??= ClassMetaFactory::build($class);
    }
}
