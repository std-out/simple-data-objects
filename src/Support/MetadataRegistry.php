<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

final class MetadataRegistry
{
    /**
     * Distinct suffix so clearCache() can never delete foreign .php files
     * if the storage path points at a shared directory.
     */
    private const string FILE_SUFFIX = '.meta.php';

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
        HydratorCompiler::flush();
        SerializerCompiler::flush();

        if (self::$storagePath === null || ! is_dir(self::$storagePath)) {
            return;
        }

        foreach (glob(self::$storagePath.'/*'.self::FILE_SUFFIX) ?: [] as $file) {
            @unlink($file);
        }
    }

    public static function isPersisted(string $class): bool
    {
        return self::$storagePath !== null && is_file(self::cacheFile($class));
    }

    /** @internal for testing only */
    public static function flush(): void
    {
        self::$cache = [];
        HydratorCompiler::flush();
        SerializerCompiler::flush();
    }

    private static function load(string $class): ClassMeta
    {
        if (self::$storagePath === null) {
            return ClassMetaFactory::build($class);
        }

        $file = self::cacheFile($class);

        if (is_file($file)) {
            $cached = require $file;

            // v2 format: [meta, hydrator, serializer] — restore the compiled
            // closures too, so warmed processes skip eval entirely (opcache
            // serves the whole file precompiled).
            if (is_array($cached)) {
                [$meta, $hydrator, $serializer] = $cached;
                HydratorCompiler::$hydrators[$class] = $hydrator;
                SerializerCompiler::$serializers[$class] = \Closure::bind($serializer, null, $class);

                return $meta;
            }

            return $cached;
        }

        $meta = ClassMetaFactory::build($class);
        self::persist($class, $file, $meta);

        return $meta;
    }

    private static function cacheFile(string $class): string
    {
        // sha256 of class name: no traversal risk, no length issues, deterministic
        return self::$storagePath.'/'.hash('sha256', $class).self::FILE_SUFFIX;
    }

    private static function persist(string $class, string $file, ClassMeta $meta): void
    {
        if (! self::isExportable($meta)) {
            return;
        }

        $dir = dirname($file);

        // Racing processes may create the directory between checks
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return;
        }

        $export = var_export($meta, true);
        $hydrator = HydratorCompiler::generate($class, $meta);
        $serializer = SerializerCompiler::generate($class, $meta);

        $code = "<?php\n\n"
            ."\$meta = {$export};\n"
            ."\$p = \$meta->parameters;\n"
            ."\$pipes = \$meta->pipes;\n\n"
            ."return [\n"
            ."    \$meta,\n"
            ."    {$hydrator},\n"
            ."    {$serializer},\n"
            ."];\n";

        // Write to a temp file then rename — atomic on POSIX systems
        $tmp = $file.'.tmp.'.getmypid();

        if (file_put_contents($tmp, $code) === false) {
            return;
        }

        if (! rename($tmp, $file)) {
            @unlink($tmp);
        }
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
