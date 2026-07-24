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

    /**
     * Populators for constructor-less classes: assign properties onto an
     * already-constructed instance (used for the lazy-ghost path and for
     * with(), since there's no constructor to inject arguments through).
     * Bound to the class scope so readonly property writes are legal.
     *
     * @internal Read directly by BaseData::fromLazy() and BaseData::with() — do not mutate.
     *
     * @var array<class-string, Closure(array, object): void>
     */
    public static array $populators = [];

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

        // Not "unused": the eval'd source below contains a literal
        // `use ($p, $pipes)` — these names are captured by reference to the
        // generated closure, not read directly in this method.
        $p = $meta->parameters;
        $pipes = $meta->pipes;

        $fn = eval('return '.self::generate($class, $meta).';');

        // Any direct property assignment (constructor-less, or a hybrid
        // class's extra properties) may target a readonly property, which
        // requires the closure to be bound to the class scope to write.
        $needsBinding = ! $meta->hasConstructor || $meta->hasExtraProperties;

        return self::$hydrators[$class] = $needsBinding ? Closure::bind($fn, null, $class) : $fn;
    }

    /** @param class-string $class */
    public static function compileArgs(string $class): Closure
    {
        $meta = MetadataRegistry::get($class);

        // Not "unused": the eval'd source below contains a literal
        // `use ($p, $pipes)` — these names are captured by reference to the
        // generated closure, not read directly in this method.
        $p = $meta->parameters;
        $pipes = $meta->pipes;

        return self::$argResolvers[$class] = eval('return '.self::generateArgs($class, $meta).';');
    }

    /**
     * @param  class-string  $class
     * @return Closure(array, object): void
     */
    public static function compilePopulate(string $class): Closure
    {
        $meta = MetadataRegistry::get($class);

        // Not "unused": the eval'd source below contains a literal
        // `use ($p, $pipes)` — these names are captured by reference to the
        // generated closure, not read directly in this method.
        $p = $meta->parameters;
        $pipes = $meta->pipes;

        $fn = eval('return '.self::generatePopulate($class, $meta).';');

        return self::$populators[$class] = Closure::bind($fn, null, $class);
    }

    public static function flush(): void
    {
        self::$hydrators = [];
        self::$argResolvers = [];
        self::$populators = [];
    }

    /**
     * Returns the closure source expression. Expects `$p` (parameter list)
     * and `$pipes` in the evaluating scope.
     *
     * @internal also used by MetadataRegistry to persist compiled code
     */
    public static function generate(string $class, ClassMeta $meta): string
    {
        if (! $meta->hasConstructor) {
            $body = self::buildPropertyAssignments($class, $meta);

            return <<<PHP
            static function (array \$d) use (\$p, \$pipes): \\{$class} {
                \$o = new \\{$class}();
            {$body}    return \$o;
            }
            PHP;
        }

        [$body, $argList] = self::buildParts($class, $meta);

        if ($meta->hasExtraProperties) {
            $extraBody = self::buildPropertyAssignments($class, $meta, includePipesPrelude: false);

            return <<<PHP
            static function (array \$d) use (\$p, \$pipes): \\{$class} {
            {$body}    \$o = new \\{$class}({$argList});
            {$extraBody}    return \$o;
            }
            PHP;
        }

        return <<<PHP
        static function (array \$d) use (\$p, \$pipes): \\{$class} {
        {$body}    return new \\{$class}({$argList});
        }
        PHP;
    }

    /**
     * Same expressions as generate(), returning the argument list instead of
     * the constructed instance — for lazy-ghost initializers of
     * constructor-based classes.
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
     * Property-assignment equivalent of generate()/generateArgs() for
     * constructor-less classes: assigns onto an already-constructed instance
     * instead of returning either an instance or an argument list. Used for
     * the lazy-ghost path and with().
     */
    private static function generatePopulate(string $class, ClassMeta $meta): string
    {
        $body = self::buildPropertyAssignments($class, $meta);

        return <<<PHP
        static function (array \$d, \\{$class} \$o) use (\$p, \$pipes): void {
        {$body}}
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
            // Hybrid classes: extra (non-constructor) properties are assigned
            // separately by buildPropertyAssignments(), not part of this arg list.
            if (! $param->viaConstructor) {
                continue;
            }

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

    /**
     * Statement-based equivalent of buildParts() for constructor-less classes
     * and for the extra (non-constructor) properties of a hybrid class:
     * assigns each property directly (`$o->prop = ...;`) instead of building
     * a constructor argument list.
     *
     * $includePipesPrelude is false only when the caller (generate()'s hybrid
     * branch) already ran class-level pipes once via buildParts() against the
     * same $d — every other caller needs it, including the standalone
     * populate closure used by fromLazy(), which never sees a pre-transformed
     * $d of its own.
     */
    private static function buildPropertyAssignments(string $class, ClassMeta $meta, bool $includePipesPrelude = true): string
    {
        $classExport = var_export($class, true);
        $body = '';

        if ($includePipesPrelude && $meta->pipes !== []) {
            $body .= "    \$d = \\StdOut\\SimpleDataObjects\\Support\\PipelineRunner::run(\$d, {$classExport}, \$pipes);\n";
        }

        foreach ($meta->parameters as $i => $param) {
            // Hybrid classes: constructor-sourced parameters are handled by
            // buildParts(), not here.
            if ($param->viaConstructor) {
                continue;
            }

            $target = "\$o->{$param->phpName}";

            // #[Flatten] consumes the whole input array (nested class enforced at build time)
            if ($param->flatten) {
                $body .= "    {$target} = \\StdOut\\SimpleDataObjects\\Support\\ValueCaster::cast(\$p[{$i}], \$d);\n";

                continue;
            }

            $key = var_export($param->inputName, true);

            // Missing key and explicit null both resolve to null — ?? is exact here
            if ($param->isPlain && $param->allowsNull && (! $param->hasDefault || $param->defaultValue === null)) {
                $body .= "    {$target} = \$d[{$key}] ?? null;\n";

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

            $body .= "    {$target} = \\array_key_exists({$key}, \$d) ? {$present} : {$absent};\n";
        }

        return $body;
    }
}
