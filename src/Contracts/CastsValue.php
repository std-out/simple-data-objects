<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface CastsValue
{
    public function get(mixed $value): mixed;

    public function set(mixed $value): mixed;
}
