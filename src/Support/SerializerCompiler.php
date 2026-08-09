<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use Closure;

/**
 * Compiles a specialized toArray() closure per data class. Hidden parameters
 * are dropped at build time; ignoreIfNull / flatten / casters become inline
 * statements; complex values delegate to ValueNormalizer. The closure is
 * scope-bound to the data class so non-public promoted properties remain
 * readable, mirroring the interpreted get_object_vars() behavior.
 *
 * Same code-generation invariants as HydratorCompiler: identifiers come from
 * reflection, free-form strings are embedded via var_export().
 */
final class SerializerCompiler
{
    /**
     * @internal Read directly by BaseData::toArray() for speed — do not mutate.
     *
     * @var array<class-string, Closure(object): array>
     */
    public static array $serializers = [];

    /**
     * Non-default contexts only, compiled lazily and kept out of $serializers
     * so the no-context path (the only one most classes ever use) never pays
     * for a second array dimension.
     *
     * @internal Read directly by BaseData::toArray() for speed — do not mutate.
     *
     * @var array<class-string, array<string, Closure(object): array>>
     */
    public static array $contextualSerializers = [];

    /** @param class-string $class */
    public static function compile(string $class, ?string $context = null): Closure
    {
        // get() may already have restored a persisted closure from the file cache
        $meta = MetadataRegistry::get($class);

        if ($context === null) {
            if (isset(self::$serializers[$class])) {
                return self::$serializers[$class];
            }

            $p = $meta->parameters;

            /** @var Closure(object): array $fn */
            $fn = Closure::bind(eval('return '.self::generate($class, $meta).';'), null, $class);

            return self::$serializers[$class] = $fn;
        }

        $p = $meta->parameters;

        /** @var Closure(object): array $fn */
        $fn = Closure::bind(eval('return '.self::generate($class, $meta, $context).';'), null, $class);

        return self::$contextualSerializers[$class][$context] = $fn;
    }

    public static function flush(): void
    {
        self::$serializers = [];
        self::$contextualSerializers = [];
    }

    /**
     * Returns the closure source expression. Expects `$p` (parameter list)
     * in the evaluating scope. The caller must Closure::bind() the result to
     * the data class so non-public promoted properties stay readable.
     *
     * @internal also used by MetadataRegistry to persist compiled code
     */
    public static function generate(string $class, ClassMeta $meta, ?string $context = null): string
    {
        $contextExport = var_export($context, true);
        $body = '';

        foreach ($meta->parameters as $i => $param) {
            if ($param->isHidden && ! in_array($context, $param->hiddenExcept, true)) {
                continue;
            }

            $key = var_export($param->outputName, true);
            $body .= "    \$v = \$o->{$param->phpName};\n";

            $assign = match (true) {
                $param->flatten => "if (\$v instanceof \\StdOut\\SimpleDataObjects\\BaseData) {\n"
                    ."        \$r = \\array_merge(\$r, \$v->toArray({$contextExport}));\n"
                    ."    } else {\n"
                    ."        \$r[{$key}] = \\StdOut\\SimpleDataObjects\\Support\\ValueNormalizer::normalize(\$v, {$contextExport});\n"
                    .'    }',
                $param->caster !== null => "\$r[{$key}] = \$p[{$i}]->caster->set(\$v);",
                $param->isPlain => "\$r[{$key}] = \$v === null || \\is_scalar(\$v) ? \$v : \\StdOut\\SimpleDataObjects\\Support\\ValueNormalizer::normalize(\$v, {$contextExport});",
                default => "\$r[{$key}] = \\StdOut\\SimpleDataObjects\\Support\\ValueNormalizer::normalize(\$v, {$contextExport});",
            };

            $body .= match (true) {
                $param->isOptional && $param->ignoreIfNull => "    if (\$v !== null && ! (\$v instanceof \\StdOut\\SimpleDataObjects\\Optional)) {\n        {$assign}\n    }\n",
                $param->isOptional => "    if (! (\$v instanceof \\StdOut\\SimpleDataObjects\\Optional)) {\n        {$assign}\n    }\n",
                $param->ignoreIfNull => "    if (\$v !== null) {\n        {$assign}\n    }\n",
                default => "    {$assign}\n",
            };
        }

        foreach ($meta->computed as $method => $key) {
            $keyExport = var_export($key, true);
            $body .= "    \$r[{$keyExport}] = \\StdOut\\SimpleDataObjects\\Support\\ValueNormalizer::normalize(\$o->{$method}(), {$contextExport});\n";
        }

        return <<<PHP
        static function (\\{$class} \$o) use (\$p): array {
            \$r = [];
        {$body}    return \$r;
        }
        PHP;
    }
}
