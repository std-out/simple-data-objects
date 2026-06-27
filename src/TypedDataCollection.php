<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects;

use Illuminate\Support\Collection;

/**
 * @template T of BaseData
 *
 * @extends Collection<int, T>
 */
final class TypedDataCollection extends Collection
{
    /**
     * @param  class-string<T>  $dataClass
     *
     * @return static<T>
     */
    public static function of(string $dataClass, iterable $items = []): static
    {
        return new self(
            collect($items)->map(fn (mixed $item) => $item instanceof $dataClass ? $item : $dataClass::from($item)),
        );
    }

    /**
     * @param  callable(T, int): bool|null  $callback
     * @param  T|null  $default
     *
     * @return T|null
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        return parent::first($callback, $default);
    }

    /**
     * @param  callable(T, int): bool|null  $callback
     * @param  T|null  $default
     *
     * @return T|null
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        return parent::last($callback, $default);
    }

}
