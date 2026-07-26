<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Concerns;

/**
 * Structurally satisfies Livewire 3's `\Livewire\Wireable` contract
 * (`toLivewire()` / `fromLivewire()`) by delegating to the existing
 * toArray()/from() round trip.
 *
 * Does not `implements \Livewire\Wireable` itself, and has no dependency on
 * livewire/livewire — that interface is untyped, so these methods already
 * satisfy it structurally. The consuming class must add
 * `implements \Livewire\Wireable` itself for Livewire to recognize it.
 */
trait WireableData
{
    abstract public static function from(mixed $data): static;

    abstract public function toArray(): array;

    public function toLivewire(): array
    {
        return $this->toArray();
    }

    public static function fromLivewire(mixed $value): static
    {
        return static::from($value);
    }
}
