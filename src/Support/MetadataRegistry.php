<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

final class MetadataRegistry
{
    /** @var array<string, ClassMeta> */
    private static array $cache = [];

    private static ?string $storagePath = null;

    public static function get(string $class): ClassMeta
    {
        return self::$cache[$class] ??= self::load($class);
    }

    public static function setStoragePath(string $path): void
    {
        self::$storagePath = rtrim($path, '/');
    }

    public static function clearCache(): void
    {
        self::$cache = [];

        if (self::$storagePath === null || ! is_dir(self::$storagePath)) {
            return;
        }

        foreach (glob(self::$storagePath.'/*.php') ?: [] as $file) {
            @unlink($file);
        }
    }

    /** @internal for testing only */
    public static function flush(): void
    {
        self::$cache = [];
    }

    private static function load(string $class): ClassMeta
    {
        if (self::$storagePath === null) {
            return ClassMetaFactory::build($class);
        }

        $file = self::cacheFile($class);

        if (is_file($file)) {
            return require $file;
        }

        $meta = ClassMetaFactory::build($class);
        self::persist($file, $meta);

        return $meta;
    }

    private static function cacheFile(string $class): string
    {
        return self::$storagePath.'/'.strtr($class, '\\', '_').'.php';
    }

    private static function persist(string $file, ClassMeta $meta): void
    {
        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $payload = base64_encode(serialize($meta));

        file_put_contents(
            $file,
            "<?php return unserialize(base64_decode('{$payload}'));",
            LOCK_EX,
        );
    }
}
