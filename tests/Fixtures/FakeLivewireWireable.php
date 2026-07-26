<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

/**
 * Mirrors the exact (untyped) method shape of Livewire 3's
 * \Livewire\Wireable — without depending on livewire/livewire — so tests can
 * prove WireableData satisfies it via a real `implements` declaration.
 */
interface FakeLivewireWireable
{
    public function toLivewire();

    public static function fromLivewire($value);
}
