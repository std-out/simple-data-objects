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

    /** @param class-string $class */
    public static function compile(string $class): Closure
    {
        // get() may already have restored a persisted closure from the file cache
        $meta = MetadataRegistry::get($class);

        if (isset(self::$serializers[$class])) {
            return self::$serializers[$class];
        }

        $p = $meta->parameters;

        /** @var Closure(object): array $fn */
        $fn = Closure::bind(eval('return '.self::generate($class, $meta).';'), null, $class);

        return self::$serializers[$class] = $fn;
    }

    public static function flush(): void
    {
        self::$serializers = [];
    }

    /**
     * Returns the closure source expression. Expects `$p` (parameter list)
     * in the evaluating scope. The caller must Closure::bind() the result to
     * the data class so non-public promoted properties stay readable.
     *
     * @internal also used by MetadataRegistry to persist compiled code
     */
    public static function generate(string $class, ClassMeta $meta): string
    {
        $body = '';

        foreach ($meta->parameters as $i => $param) {
            if ($param->isHidden) {
                continue;
            }

            $key = var_export($param->inputName, true);
            $body .= "    \$v = \$o->{$param->phpName};\n";

            $assign = match (true) {
                $param->flatten => "if (\$v instanceof \\StdOut\\SimpleDataObjects\\BaseData) {\n"
                    ."        \$r = \\array_merge(\$r, \$v->toArray());\n"
                    ."    } else {\n"
                    ."        \$r[{$key}] = \\StdOut\\SimpleDataObjects\\Support\\ValueNormalizer::normalize(\$v);\n"
                    .'    }',
                $param->caster !== null => "\$r[{$key}] = \$p[{$i}]->caster->set(\$v);",
                $param->isPlain => "\$r[{$key}] = \$v === null || \\is_scalar(\$v) ? \$v : \\StdOut\\SimpleDataObjects\\Support\\ValueNormalizer::normalize(\$v);",
                default => "\$r[{$key}] = \\StdOut\\SimpleDataObjects\\Support\\ValueNormalizer::normalize(\$v);",
            };

            $body .= $param->ignoreIfNull
                ? "    if (\$v !== null) {\n        {$assign}\n    }\n"
                : "    {$assign}\n";
        }

        return <<<PHP
        static function (\\{$class} \$o) use (\$p): array {
            \$r = [];
        {$body}    return \$r;
        }
        PHP;
    }
}
