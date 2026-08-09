<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use Closure;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\HydrationResult;

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

    /**
     * Compiled separately from $hydrators, so from()/tryFrom() never pay for
     * a feature they don't use.
     *
     * @internal Read directly by BaseData::fromResult() for speed — do not mutate.
     *
     * @var array<class-string, Closure(array): HydrationResult>
     */
    public static array $collectingHydrators = [];

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

    /** @param class-string $class */
    public static function compileCollecting(string $class): Closure
    {
        $meta = MetadataRegistry::get($class);

        if (isset(self::$collectingHydrators[$class])) {
            return self::$collectingHydrators[$class];
        }

        // $p/$pipes are captured by the eval'd closure's `use()`, not read directly here.
        $p = $meta->parameters;
        $pipes = $meta->pipes;

        $fn = eval('return '.self::generateCollecting($class, $meta).';');

        $needsBinding = ! $meta->hasConstructor || $meta->hasExtraProperties;

        return self::$collectingHydrators[$class] = $needsBinding ? Closure::bind($fn, null, $class) : $fn;
    }

    public static function flush(): void
    {
        self::$hydrators = [];
        self::$argResolvers = [];
        self::$populators = [];
        self::$collectingHydrators = [];
    }

    /**
     * Returns the closure source expression. Expects `$p` (parameter list),
     * `$pipes`, and `$meta` (for #[Discriminator] dispatchers) in the
     * evaluating scope.
     *
     * @internal also used by MetadataRegistry to persist compiled code
     */
    public static function generate(string $class, ClassMeta $meta): string
    {
        // A #[Discriminator] class never hydrates itself — its "hydrator"
        // dispatches to the concrete subclass's own compiled hydrator.
        if ($meta->discriminatorField !== null) {
            $classExport = var_export($class, true);
            $field = var_export($meta->discriminatorField, true);

            return <<<PHP
            static function (array \$d) use (\$meta): \\{$class} {
                \$c = \$meta->resolveDiscriminatedClass(\$d) ?? throw \\StdOut\\SimpleDataObjects\\Exceptions\\DataHydrationException::unresolvedDiscriminator({$classExport}, {$field}, \$d[{$field}] ?? null);
                return \$c::from(\$d);
            }
            PHP;
        }

        if (! $meta->hasConstructor) {
            $unknownCheck = self::buildUnknownKeysCheck($class, $meta);
            $body = self::buildPropertyAssignments($class, $meta);

            return <<<PHP
            static function (array \$d) use (\$p, \$pipes): \\{$class} {
            {$unknownCheck}    \$o = new \\{$class}();
            {$body}    return \$o;
            }
            PHP;
        }

        $unknownCheck = self::buildUnknownKeysCheck($class, $meta);
        [$body, $argList] = self::buildParts($class, $meta);

        if ($meta->hasExtraProperties) {
            $extraBody = self::buildPropertyAssignments($class, $meta, includePipesPrelude: false);

            return <<<PHP
            static function (array \$d) use (\$p, \$pipes): \\{$class} {
            {$unknownCheck}{$body}    \$o = new \\{$class}({$argList});
            {$extraBody}    return \$o;
            }
            PHP;
        }

        return <<<PHP
        static function (array \$d) use (\$p, \$pipes): \\{$class} {
        {$unknownCheck}{$body}    return new \\{$class}({$argList});
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
        $unknownCheck = self::buildUnknownKeysCheck($class, $meta);
        [$body, $argList] = self::buildParts($class, $meta);

        return <<<PHP
        static function (array \$d) use (\$p, \$pipes): array {
        {$unknownCheck}{$body}    return [{$argList}];
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
        $unknownCheck = self::buildUnknownKeysCheck($class, $meta);
        $body = self::buildPropertyAssignments($class, $meta);

        return <<<PHP
        static function (array \$d, \\{$class} \$o) use (\$p, \$pipes): void {
        {$unknownCheck}{$body}}
        PHP;
    }

    /**
     * #[RejectUnknownKeys]: checked against the caller's raw input, before
     * pipes or per-field extraction run. Empty string when unused.
     */
    private static function buildUnknownKeysCheck(string $class, ClassMeta $meta): string
    {
        if (! $meta->rejectUnknownKeys) {
            return '';
        }

        $classExport = var_export($class, true);
        $knownKeys = self::knownKeysExport($meta);

        return "    if (\$u = \\array_diff_key(\$d, {$knownKeys})) {\n"
            ."        throw \\StdOut\\SimpleDataObjects\\Exceptions\\DataHydrationException::unknownKeys({$classExport}, \\array_keys(\$u));\n"
            ."    }\n";
    }

    /** Every accepted input alias and every #[Computed] output key, as a var_export'd set. */
    private static function knownKeysExport(ClassMeta $meta): string
    {
        $known = [];

        foreach ($meta->parameters as $param) {
            foreach ($param->inputNames as $name) {
                $known[$name] = true;
            }
        }

        foreach ($meta->computed as $key) {
            $known[$key] = true;
        }

        return var_export($known, true);
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

            $f = self::fieldAccessExpr($i, $param, $classExport, $body);

            // Missing key and explicit null both resolve to null — ?? is exact here,
            // but only for a single name: with aliases, $d[null] would read the "" key.
            if (count($param->inputNames) === 1 && $param->isPlain && $param->allowsNull && ! $param->isOptional && (! $param->hasDefault || $param->defaultValue === null)) {
                $args[] = "\$d[{$f['key']}] ?? null";

                continue;
            }

            $args[] = "{$f['presence']} ? {$f['present']} : {$f['absent']}";
        }

        $argList = $args === [] ? '' : "\n        ".implode(",\n        ", $args).",\n    ";

        return [$body, $argList];
    }

    /**
     * A single name compiles to the same literal-key codegen as before
     * (zero overhead); aliases compile to a "first present key wins"
     * ternary chain, materialized once into a `$k{i}` local.
     *
     * @param  list<string|int>  $names
     * @return array{0: string, 1: string} key expression, presence-check expression
     */
    private static function resolveKeyExpr(int $i, array $names, string &$body): array
    {
        if (count($names) === 1) {
            $key = var_export($names[0], true);

            return [$key, "\\array_key_exists({$key}, \$d)"];
        }

        $expr = 'null';

        foreach (array_reverse($names) as $name) {
            $nameExport = var_export($name, true);
            $expr = "\\array_key_exists({$nameExport}, \$d) ? {$nameExport} : ({$expr})";
        }

        $var = "\$k{$i}";
        $body .= "    {$var} = {$expr};\n";

        return [$var, "{$var} !== null"];
    }

    /**
     * Shared per-parameter pieces: the hot path inlines them into a ternary,
     * the collecting path wraps them in try/catch.
     *
     * @return array{key: string, presence: string, present: string, absent: string, missingKey: string}
     */
    private static function fieldAccessExpr(int $i, ParameterMeta $param, string $classExport, string &$body): array
    {
        [$key, $presence] = self::resolveKeyExpr($i, $param->inputNames, $body);

        $present = $param->isPlain
            ? "\$d[{$key}]"
            : "\\StdOut\\SimpleDataObjects\\Support\\ValueCaster::cast(\$p[{$i}], \$d[{$key}])";

        $missingKey = var_export($param->inputNames[0], true);

        $absent = match (true) {
            $param->isOptional => '\\StdOut\\SimpleDataObjects\\Optional::missing()',
            $param->hasDefault => "\$p[{$i}]->defaultValue",
            $param->allowsNull => 'null',
            default => "throw \\StdOut\\SimpleDataObjects\\Exceptions\\DataHydrationException::missingField({$classExport}, {$missingKey})",
        };

        return ['key' => $key, 'presence' => $presence, 'present' => $present, 'absent' => $absent, 'missingKey' => $missingKey];
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

            $f = self::fieldAccessExpr($i, $param, $classExport, $body);

            // Missing key and explicit null both resolve to null — ?? is exact here.
            // Only safe for a single accepted name; see resolveKeyExpr().
            if (count($param->inputNames) === 1 && $param->isPlain && $param->allowsNull && ! $param->isOptional && (! $param->hasDefault || $param->defaultValue === null)) {
                $body .= "    {$target} = \$d[{$f['key']}] ?? null;\n";

                continue;
            }

            $body .= "    {$target} = {$f['presence']} ? {$f['present']} : {$f['absent']};\n";
        }

        return $body;
    }

    /**
     * Error-accumulating counterpart of generate(): returns a HydrationResult
     * instead of an instance or a thrown exception.
     *
     * @internal also used by BaseData::fromResult()
     */
    public static function generateCollecting(string $class, ClassMeta $meta): string
    {
        $resultClass = '\\StdOut\\SimpleDataObjects\\HydrationResult';

        // Same dispatch as generate(), but an unresolved discriminator is a failed result, not a throw.
        if ($meta->discriminatorField !== null) {
            $classExport = var_export($class, true);
            $field = var_export($meta->discriminatorField, true);

            return <<<PHP
            static function (array \$d) use (\$meta): {$resultClass} {
                \$c = \$meta->resolveDiscriminatedClass(\$d);
                if (\$c === null) {
                    return {$resultClass}::failure([{$field} => \\StdOut\\SimpleDataObjects\\Exceptions\\DataHydrationException::unresolvedDiscriminator({$classExport}, {$field}, \$d[{$field}] ?? null)->getMessage()]);
                }
                return \$c::fromResult(\$d);
            }
            PHP;
        }

        $unknownCheck = self::buildCollectingUnknownCheck($class, $meta);

        if (! $meta->hasConstructor) {
            $fields = self::buildCollectingPropertyAssignments($class, $meta);

            return <<<PHP
            static function (array \$d) use (\$p, \$pipes): {$resultClass} {
                \$errors = [];
            {$unknownCheck}    \$o = new \\{$class}();
            {$fields}    return \$errors === [] ? {$resultClass}::success(\$o) : {$resultClass}::failure(\$errors);
            }
            PHP;
        }

        [$pipelineBlock, $guardedFieldBody, $argList] = self::buildCollectingParts($class, $meta);

        if ($meta->hasExtraProperties) {
            $extraFields = self::buildCollectingPropertyAssignments($class, $meta, includePipesPrelude: false);

            return <<<PHP
            static function (array \$d) use (\$p, \$pipes): {$resultClass} {
                \$errors = [];
            {$unknownCheck}{$pipelineBlock}{$guardedFieldBody}    \$o = null;
                if (\$errors === []) {
                    try {
                        \$o = new \\{$class}({$argList});
                    } catch (\\Throwable \$e) {
                        \$errors['\$construct'] = \$e->getMessage();
                    }
                }
                if (\$o !== null) {
            {$extraFields}    }
                return \$errors === [] ? {$resultClass}::success(\$o) : {$resultClass}::failure(\$errors);
            }
            PHP;
        }

        return <<<PHP
        static function (array \$d) use (\$p, \$pipes): {$resultClass} {
            \$errors = [];
        {$unknownCheck}{$pipelineBlock}{$guardedFieldBody}    if (\$errors !== []) {
                return {$resultClass}::failure(\$errors);
            }
            try {
                return {$resultClass}::success(new \\{$class}({$argList}));
            } catch (\\Throwable \$e) {
                return {$resultClass}::failure(['\$construct' => \$e->getMessage()]);
            }
        }
        PHP;
    }

    /** Non-blocking counterpart of buildUnknownKeysCheck(): records '$unknown' instead of throwing. */
    private static function buildCollectingUnknownCheck(string $class, ClassMeta $meta): string
    {
        if (! $meta->rejectUnknownKeys) {
            return '';
        }

        $classExport = var_export($class, true);
        $knownKeys = self::knownKeysExport($meta);

        return "    if (\$u = \\array_diff_key(\$d, {$knownKeys})) {\n"
            ."        \$errors['\$unknown'] = \\StdOut\\SimpleDataObjects\\Exceptions\\DataHydrationException::unknownKeys({$classExport}, \\array_keys(\$u))->getMessage();\n"
            ."    }\n";
    }

    /**
     * A failing class-level #[Pipe] becomes a '$pipeline' error and flips
     * $pipelineOk, so callers skip field extraction on an unreliable $d.
     */
    private static function buildCollectingPipelinePrelude(string $class, ClassMeta $meta): string
    {
        if ($meta->pipes === []) {
            return '';
        }

        $classExport = var_export($class, true);

        return "    \$pipelineOk = true;\n"
            ."    try {\n"
            ."        \$d = \\StdOut\\SimpleDataObjects\\Support\\PipelineRunner::run(\$d, {$classExport}, \$pipes);\n"
            ."    } catch (\\Throwable \$e) {\n"
            ."        \$errors['\$pipeline'] = \$e->getMessage();\n"
            ."        \$pipelineOk = false;\n"
            ."    }\n";
    }

    /**
     * @return array{0: string, 1: string, 2: string} pipeline block, $pipelineOk-guarded field statements, constructor argument list
     */
    private static function buildCollectingParts(string $class, ClassMeta $meta): array
    {
        $pipelineBlock = self::buildCollectingPipelinePrelude($class, $meta);

        $fieldBody = '';
        $args = [];

        foreach ($meta->parameters as $i => $param) {
            if (! $param->viaConstructor) {
                continue;
            }

            $target = "\$v{$i}";
            $fieldBody .= self::buildCollectingField($i, $param, $class, $target);
            $args[] = $target;
        }

        $guardedFieldBody = $meta->pipes !== [] ? "    if (\$pipelineOk) {\n{$fieldBody}    }\n" : $fieldBody;

        return [$pipelineBlock, $guardedFieldBody, implode(', ', $args)];
    }

    /** Collecting equivalent of buildPropertyAssignments(): assigns onto $o, recording failures into $errors instead of throwing. */
    private static function buildCollectingPropertyAssignments(string $class, ClassMeta $meta, bool $includePipesPrelude = true): string
    {
        $pipelineBlock = $includePipesPrelude ? self::buildCollectingPipelinePrelude($class, $meta) : '';
        $hasGate = $includePipesPrelude && $meta->pipes !== [];

        $fieldBody = '';

        foreach ($meta->parameters as $i => $param) {
            if ($param->viaConstructor) {
                continue;
            }

            $target = "\$o->{$param->phpName}";
            $fieldBody .= self::buildCollectingField($i, $param, $class, $target);
        }

        $guardedFieldBody = $hasGate ? "    if (\$pipelineOk) {\n{$fieldBody}    }\n" : $fieldBody;

        return $pipelineBlock.$guardedFieldBody;
    }

    /**
     * Dispatched by shape: #[Flatten] and nested BaseData/#[DataCollection]
     * fields (unless overridden by #[Cast]) recurse into fromResult() with
     * dot-path errors; everything else reuses fieldAccessExpr() in a try/catch.
     */
    private static function buildCollectingField(int $i, ParameterMeta $param, string $class, string $target): string
    {
        if ($param->flatten) {
            return self::buildCollectingFlattenField($i, $param, $target);
        }

        if ($param->caster === null && $param->dataCollectionClass !== null) {
            return self::buildCollectingCollectionField($i, $param, $class, $target);
        }

        if ($param->caster === null && $param->nestedDataClass !== null) {
            return self::buildCollectingNestedField($i, $param, $class, $target);
        }

        $classExport = var_export($class, true);
        $fieldBody = '';
        $f = self::fieldAccessExpr($i, $param, $classExport, $fieldBody);

        return $fieldBody
            ."    try {\n"
            ."        {$target} = {$f['presence']} ? {$f['present']} : {$f['absent']};\n"
            ."    } catch (\\Throwable \$e) {\n"
            ."        \$errors[{$f['missingKey']}] = \$e->getMessage();\n"
            ."    }\n";
    }

    private static function buildCollectingFlattenField(int $i, ParameterMeta $param, string $target): string
    {
        $nestedClass = "\\{$param->nestedDataClass}";
        $fr = "\$fr{$i}";

        return "    {$fr} = {$nestedClass}::fromResult(\$d);\n"
            ."    if ({$fr}->ok()) {\n"
            ."        {$target} = {$fr}->value();\n"
            ."    } else {\n"
            ."        foreach ({$fr}->errors() as \$ek => \$em) {\n"
            ."            \$errors[\$ek] = \$em;\n"
            ."        }\n"
            ."    }\n";
    }

    private static function buildCollectingNestedField(int $i, ParameterMeta $param, string $class, string $target): string
    {
        $classExport = var_export($class, true);
        $nestedClass = "\\{$param->nestedDataClass}";
        $prefix = var_export((string) $param->inputNames[0], true);

        $fieldBody = '';
        [$key, $presence] = self::resolveKeyExpr($i, $param->inputNames, $fieldBody);
        $missingKey = var_export($param->inputNames[0], true);

        $absent = match (true) {
            $param->isOptional => "{$target} = \\StdOut\\SimpleDataObjects\\Optional::missing();",
            $param->hasDefault => "{$target} = \$p[{$i}]->defaultValue;",
            $param->allowsNull => "{$target} = null;",
            default => "\$errors[{$missingKey}] = \\StdOut\\SimpleDataObjects\\Exceptions\\DataHydrationException::missingField({$classExport}, {$missingKey})->getMessage();",
        };

        $raw = "\$raw{$i}";
        $fr = "\$fr{$i}";
        $pipe = $param->pipes !== []
            ? "            {$raw} = \\StdOut\\SimpleDataObjects\\Support\\PipelineRunner::runOnValue({$raw}, ".var_export($param->phpName, true).", \$p[{$i}]->pipes);\n"
            : '';

        return $fieldBody
            ."    if ({$presence}) {\n"
            ."        try {\n"
            ."            {$raw} = \$d[{$key}];\n"
            .$pipe
            ."            if ({$raw} === null) {\n"
            ."                {$target} = null;\n"
            ."            } elseif ({$raw} instanceof {$nestedClass}) {\n"
            ."                {$target} = {$raw};\n"
            ."            } else {\n"
            ."                {$fr} = {$nestedClass}::fromResult({$raw});\n"
            ."                if ({$fr}->ok()) {\n"
            ."                    {$target} = {$fr}->value();\n"
            ."                } else {\n"
            ."                    foreach ({$fr}->errors() as \$ek => \$em) {\n"
            ."                        \$errors[{$prefix}.'.'.\$ek] = \$em;\n"
            ."                    }\n"
            ."                }\n"
            ."            }\n"
            ."        } catch (\\Throwable \$e) {\n"
            ."            \$errors[{$missingKey}] = \$e->getMessage();\n"
            ."        }\n"
            ."    } else {\n"
            ."        {$absent}\n"
            ."    }\n";
    }

    private static function buildCollectingCollectionField(int $i, ParameterMeta $param, string $class, string $target): string
    {
        $classExport = var_export($class, true);
        $itemClass = "\\{$param->dataCollectionClass}";
        $prefix = var_export((string) $param->inputNames[0], true);

        $fieldBody = '';
        [$key, $presence] = self::resolveKeyExpr($i, $param->inputNames, $fieldBody);
        $missingKey = var_export($param->inputNames[0], true);

        $absent = match (true) {
            $param->isOptional => "{$target} = \\StdOut\\SimpleDataObjects\\Optional::missing();",
            $param->hasDefault => "{$target} = \$p[{$i}]->defaultValue;",
            $param->allowsNull => "{$target} = null;",
            default => "\$errors[{$missingKey}] = \\StdOut\\SimpleDataObjects\\Exceptions\\DataHydrationException::missingField({$classExport}, {$missingKey})->getMessage();",
        };

        $raw = "\$raw{$i}";
        $items = "\$items{$i}";
        $ok = "\$itemsOk{$i}";
        $idx = "\$idx{$i}";
        $item = "\$item{$i}";
        $ir = "\$ir{$i}";
        $nullBranch = $param->allowsNull ? 'null' : 'new \\StdOut\\SimpleDataObjects\\TypedDataCollection()';
        $pipe = $param->pipes !== []
            ? "            {$raw} = \\StdOut\\SimpleDataObjects\\Support\\PipelineRunner::runOnValue({$raw}, ".var_export($param->phpName, true).", \$p[{$i}]->pipes);\n"
            : '';

        return $fieldBody
            ."    if ({$presence}) {\n"
            ."        try {\n"
            ."            {$raw} = \$d[{$key}];\n"
            .$pipe
            ."            if ({$raw} === null) {\n"
            ."                {$target} = {$nullBranch};\n"
            ."            } else {\n"
            ."                {$items} = [];\n"
            ."                {$ok} = true;\n"
            ."                foreach ((\\is_iterable({$raw}) ? {$raw} : (array) {$raw}) as {$idx} => {$item}) {\n"
            ."                    if ({$item} instanceof {$itemClass}) {\n"
            ."                        {$items}[] = {$item};\n"
            ."                        continue;\n"
            ."                    }\n"
            ."                    {$ir} = {$itemClass}::fromResult({$item});\n"
            ."                    if ({$ir}->ok()) {\n"
            ."                        {$items}[] = {$ir}->value();\n"
            ."                    } else {\n"
            ."                        {$ok} = false;\n"
            ."                        foreach ({$ir}->errors() as \$ek => \$em) {\n"
            ."                            \$errors[{$prefix}.'.'.{$idx}.'.'.\$ek] = \$em;\n"
            ."                        }\n"
            ."                    }\n"
            ."                }\n"
            ."                if ({$ok}) {\n"
            ."                    {$target} = new \\StdOut\\SimpleDataObjects\\TypedDataCollection({$items});\n"
            ."                }\n"
            ."            }\n"
            ."        } catch (\\Throwable \$e) {\n"
            ."            \$errors[{$missingKey}] = \$e->getMessage();\n"
            ."        }\n"
            ."    } else {\n"
            ."        {$absent}\n"
            ."    }\n";
    }
}
