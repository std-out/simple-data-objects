<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Pipes;

use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class TrimValuePipe implements ValuePipe
{
    public function handle(mixed $value, string $paramName, callable $next): mixed
    {
        return $next(is_string($value) ? trim($value) : $value);
    }
}
