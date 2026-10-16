<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface CastsValue
{
    public function get(#[\SensitiveParameter] mixed $value): mixed;

    public function set(#[\SensitiveParameter] mixed $value): mixed;
}
