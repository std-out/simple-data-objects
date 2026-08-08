<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class NoopValuePipe implements ValuePipe
{
    public function handle(mixed $value, string $paramName, callable $next): mixed
    {
        return $next($value);
    }
}
