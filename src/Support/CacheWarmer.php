<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use PhpToken;
use ReflectionClass;
use StdOut\SimpleDataObjects\BaseData;

/**
 * Pre-builds the metadata + compiled-code file cache for every BaseData
 * subclass found under the given paths, so the first request after a deploy
 * pays neither reflection nor closure compilation (see bin/sdo-warm).
 */
final class CacheWarmer
{
    /**
     * @param  list<string>  $paths  files or directories to scan for data classes
     * @return array{warmed: list<class-string>, skipped: list<class-string>}
     */
    public static function warm(string $storagePath, array $paths): array
    {
        MetadataRegistry::setStoragePath($storagePath);

        $warmed = [];
        $skipped = [];

        foreach (self::discover($paths) as $class) {
            try {
                MetadataRegistry::get($class);
            } catch (\Throwable $e) {
                // Fail fast, but name the offending class — deploy logs must be actionable
                throw new \RuntimeException("Cannot warm {$class}: {$e->getMessage()}", 0, $e);
            }

            if (MetadataRegistry::isPersisted($class)) {
                $warmed[] = $class;
            } else {
                // Non-exportable metadata (EncryptedCast, object rules)
                $skipped[] = $class;
            }
        }

        return ['warmed' => $warmed, 'skipped' => $skipped];
    }

    /**
     * PSR-4 source directories declared in a composer.json — the default
     * scan scope when the CLI is invoked without explicit paths.
     *
     * @return list<string>
     */
    public static function pathsFromComposer(string $composerFile): array
    {
        if (! is_file($composerFile)) {
            return [];
        }

        $config = json_decode((string) file_get_contents($composerFile), true);

        if (! is_array($config)) {
            return [];
        }

        $root = dirname($composerFile);
        $paths = [];

        foreach ($config['autoload']['psr-4'] ?? [] as $dirs) {
            foreach ((array) $dirs as $dir) {
                $paths[] = $root.'/'.rtrim((string) $dir, '/');
            }
        }

        return $paths;
    }

    /**
     * @param  list<string>  $paths
     * @return list<class-string<BaseData>>
     */
    public static function discover(array $paths): array
    {
        $classes = [];

        foreach ($paths as $path) {
            foreach (self::phpFiles($path) as $file) {
                foreach (self::classesInFile($file) as $fqcn) {
                    if (is_subclass_of($fqcn, BaseData::class) && ! (new ReflectionClass($fqcn))->isAbstract()) {
                        $classes[] = $fqcn;
                    }
                }
            }
        }

        sort($classes);

        return $classes;
    }

    /** @return list<string> */
    private static function phpFiles(string $path): array
    {
        if (is_file($path)) {
            return [$path];
        }

        if (! is_dir($path)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $entry) {
            if ($entry->isFile() && $entry->getExtension() === 'php') {
                $files[] = $entry->getPathname();
            }
        }

        return $files;
    }

    /**
     * Extracts fully-qualified class names declared in a file, ignoring
     * `::class` constants and anonymous classes.
     *
     * @return list<string>
     */
    private static function classesInFile(string $file): array
    {
        $tokens = PhpToken::tokenize((string) file_get_contents($file));
        $count = count($tokens);
        $namespace = '';
        $classes = [];

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token->id === T_NAMESPACE) {
                $next = self::nextSignificant($tokens, $i, $count);
                $namespace = $next !== null ? $next->text.'\\' : '';

                continue;
            }

            if ($token->id !== T_CLASS) {
                continue;
            }

            $prev = self::previousSignificant($tokens, $i);

            // Foo::class constant or `new class` (anonymous)
            if ($prev !== null && ($prev->id === T_DOUBLE_COLON || $prev->id === T_NEW)) {
                continue;
            }

            $next = self::nextSignificant($tokens, $i, $count);

            if ($next !== null && $next->id === T_STRING) {
                $classes[] = $namespace.$next->text;
            }
        }

        return $classes;
    }

    /** @param  list<PhpToken>  $tokens */
    private static function nextSignificant(array $tokens, int $index, int $count): ?PhpToken
    {
        for ($i = $index + 1; $i < $count; $i++) {
            if (! $tokens[$i]->isIgnorable()) {
                return $tokens[$i];
            }
        }

        return null;
    }

    /** @param  list<PhpToken>  $tokens */
    private static function previousSignificant(array $tokens, int $index): ?PhpToken
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (! $tokens[$i]->isIgnorable()) {
                return $tokens[$i];
            }
        }

        return null;
    }
}
