<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Pipes;

use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class NullifyEmptyStringValuePipe implements ValuePipe
{
    public function handle(mixed $value, string $paramName, callable $next): mixed
    {
        return $next($value === '' ? null : $value);
    }
}
