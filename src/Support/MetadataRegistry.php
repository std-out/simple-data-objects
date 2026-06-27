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
        self::$storagePath = $path !== '' ? rtrim($path, '/') : null;
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
        // sha256 of class name: no traversal risk, no length issues, deterministic
        return self::$storagePath.'/'.hash('sha256', $class).'.php';
    }

    private static function persist(string $file, ClassMeta $meta): void
    {
        if (! self::isExportable($meta)) {
            return;
        }

        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $export = var_export($meta, true);

        // Write to a temp file then rename — atomic on POSIX systems
        $tmp = $file.'.tmp.'.getmypid();
        file_put_contents($tmp, "<?php\n\nreturn {$export};\n");
        rename($tmp, $file);
    }

    /**
     * Only classes whose entire metadata graph supports var_export() can be
     * persisted. Casters must implement __set_state(); rule objects (e.g.
     * Illuminate Rule instances) are not exportable — only string rules are.
     */
    private static function isExportable(ClassMeta $meta): bool
    {
        foreach ($meta->parameters as $param) {
            if ($param->caster !== null && ! method_exists($param->caster, '__set_state')) {
                return false;
            }

            foreach ($param->rules as $rule) {
                if (! is_string($rule)) {
                    return false;
                }
            }
        }

        return true;
    }
}
