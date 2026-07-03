<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

use Illuminate\Support\Collection;
use StdOut\SimpleDataObjects\Support\HydratorCompiler;
use StdOut\SimpleDataObjects\Support\InputNormalizer;

/**
 * @template T of BaseData
 *
 * @extends Collection<int, T>
 */
final class TypedDataCollection extends Collection
{
    /**
     * @param  class-string<T>  $dataClass
     * @return static<T>
     */
    public static function of(string $dataClass, iterable $items = []): static
    {
        // Resolved once — the per-item loop pays only instanceof + one call
        $hydrate = HydratorCompiler::$hydrators[$dataClass] ?? HydratorCompiler::compile($dataClass);
        $result = [];

        foreach ($items as $item) {
            $result[] = $item instanceof $dataClass
                ? $item
                : $hydrate(is_array($item) ? $item : InputNormalizer::normalize($dataClass, $item));
        }

        return new self($result);
    }

    /**
     * @param  callable(T, int): bool|null  $callback
     * @param  T|null  $default
     * @return T|null
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        return parent::first($callback, $default);
    }

    /**
     * @param  callable(T, int): bool|null  $callback
     * @param  T|null  $default
     * @return T|null
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        return parent::last($callback, $default);
    }
}
