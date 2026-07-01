<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface ValuePipe
{
    public function handle(mixed $value, string $paramName, callable $next): mixed;
}
