<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class NonExportableCast implements CastsValue
{
    public function get(mixed $value): mixed
    {
        return $value;
    }

    public function set(mixed $value): mixed
    {
        return $value;
    }
}
