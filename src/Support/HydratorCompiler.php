<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use Closure;
use StdOut\SimpleDataObjects\BaseData;

/**
 * Compiles a specialized hydration closure per data class: plain parameters
 * become inline array reads, everything else (casts, enums, nested DTOs,
 * collections, pipes) delegates to the regular runtime via the captured
 * ParameterMeta list — so behavior is identical to the interpreted path,
 * minus the per-parameter dispatch overhead.
 *
 * Generated code is assembled ONLY from reflection metadata: class names are
 * valid identifiers by definition, and input names (the only free-form
 * strings, e.g. from #[MapPropertyName]) are embedded via var_export().
 */
final class HydratorCompiler
{
    /**
     * @internal Read directly by BaseData::from() for speed — do not mutate.
     *
     * @var array<class-string, Closure(array): object>
     */
    public static array $hydrators = [];

    /**
     * Argument-list resolvers for the lazy-ghost path: same generated
     * expressions as the hydrators, but returning the constructor arguments
     * instead of the instance (a ghost initializes itself via __construct).
     *
     * @internal Read directly by BaseData::fromLazy() — do not mutate.
     *
     * @var array<class-string, Closure(array): array>
     */
    public static array $argResolvers = [];

    /** @param class-string $class */
    public static function compile(string $class): Closure
    {
        // Reachable with a caller-supplied class via TypedDataCollection::of()
        if (! is_subclass_of($class, BaseData::class)) {
            throw new \InvalidArgumentException("{$class} must extend ".BaseData::class.' to be hydrated.');
        }

        // get() may already have restored a persisted closure from the file cache
        $meta = MetadataRegistry::get($class);

        if (isset(self::$hydrators[$class])) {
            return self::$hydrators[$class];
        }

        $p = $meta->parameters;
        $pipes = $meta->pipes;

        return self::$hydrators[$class] = eval('return '.self::generate($class, $meta).';');
    }

    /** @param class-string $class */
    public static function compileArgs(string $class): Closure
    {
        $meta = MetadataRegistry::get($class);

        $p = $meta->parameters;
        $pipes = $meta->pipes;

        return self::$argResolvers[$class] = eval('return '.self::generateArgs($class, $meta).';');
    }

    public static function flush(): void
    {
        self::$hydrators = [];
        self::$argResolvers = [];
    }

    /**
     * Returns the closure source expression. Expects `$p` (parameter list)
     * and `$pipes` in the evaluating scope.
     *
     * @internal also used by MetadataRegistry to persist compiled code
     */
    public static function generate(string $class, ClassMeta $meta): string
    {
        [$body, $argList] = self::buildParts($class, $meta);

        return <<<PHP
        static function (array \$d) use (\$p, \$pipes): \\{$class} {
        {$body}    return new \\{$class}({$argList});
        }
        PHP;
    }

    /**
     * Same expressions as generate(), returning the argument list instead of
     * the constructed instance — for lazy-ghost initializers.
     */
    private static function generateArgs(string $class, ClassMeta $meta): string
    {
        [$body, $argList] = self::buildParts($class, $meta);

        return <<<PHP
        static function (array \$d) use (\$p, \$pipes): array {
        {$body}    return [{$argList}];
        }
        PHP;
    }

    /**
     * The single source of hydration-argument semantics — both closure
     * flavors are assembled from this output.
     *
     * @return array{0: string, 1: string} pipeline body and argument list
     */
    private static function buildParts(string $class, ClassMeta $meta): array
    {
        $classExport = var_export($class, true);
        $body = '';

        if ($meta->pipes !== []) {
            $body .= "    \$d = \\StdOut\\SimpleDataObjects\\Support\\PipelineRunner::run(\$d, {$classExport}, \$pipes);\n";
        }

        $args = [];

        foreach ($meta->parameters as $i => $param) {
            // #[Flatten] consumes the whole input array (nested class enforced at build time)
            if ($param->flatten) {
                $args[] = "\\StdOut\\SimpleDataObjects\\Support\\ValueCaster::cast(\$p[{$i}], \$d)";

                continue;
            }

            $key = var_export($param->inputName, true);

            // Missing key and explicit null both resolve to null — ?? is exact here
            if ($param->isPlain && $param->allowsNull && (! $param->hasDefault || $param->defaultValue === null)) {
                $args[] = "\$d[{$key}] ?? null";

                continue;
            }

            $present = $param->isPlain
                ? "\$d[{$key}]"
                : "\\StdOut\\SimpleDataObjects\\Support\\ValueCaster::cast(\$p[{$i}], \$d[{$key}])";

            $absent = match (true) {
                $param->hasDefault => "\$p[{$i}]->defaultValue",
                $param->allowsNull => 'null',
                default => "throw \\StdOut\\SimpleDataObjects\\Exceptions\\DataHydrationException::missingField({$classExport}, {$key})",
            };

            $args[] = "\\array_key_exists({$key}, \$d) ? {$present} : {$absent}";
        }

        $argList = $args === [] ? '' : "\n        ".implode(",\n        ", $args).",\n    ";

        return [$body, $argList];
    }
}
