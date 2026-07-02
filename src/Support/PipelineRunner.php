<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\Contracts\DataPipe;
use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class PipelineRunner
{
    /**
     * Pipes are stateless by contract, so one instance per class is reused
     * across all hydrations in the process.
     *
     * @var array<class-string, DataPipe|ValuePipe>
     */
    private static array $instances = [];

    /**
     * Class-level: transforms the full input array through DataPipe instances.
     *
     * @param  list<class-string<DataPipe>>  $pipes
     * @param  class-string  $dataClass
     */
    public static function run(array $data, string $dataClass, array $pipes): array
    {
        if ($pipes === []) {
            return $data;
        }

        $pipeline = array_reduce(
            array_reverse($pipes),
            static fn (callable $next, string $pipeClass): \Closure => static fn (array $d): array => self::instance($pipeClass)->handle($d, $dataClass, $next),
            static fn (array $d): array => $d,
        );

        return $pipeline($data);
    }

    /**
     * Parameter-level: transforms a single value through ValuePipe instances.
     *
     * @param  list<class-string<ValuePipe>>  $pipes
     */
    public static function runOnValue(mixed $value, string $paramName, array $pipes): mixed
    {
        if ($pipes === []) {
            return $value;
        }

        $pipeline = array_reduce(
            array_reverse($pipes),
            static fn (callable $next, string $pipeClass): \Closure => static fn (mixed $v): mixed => self::instance($pipeClass)->handle($v, $paramName, $next),
            static fn (mixed $v): mixed => $v,
        );

        return $pipeline($value);
    }

    private static function instance(string $pipeClass): DataPipe|ValuePipe
    {
        return self::$instances[$pipeClass] ??= new $pipeClass;
    }
}
