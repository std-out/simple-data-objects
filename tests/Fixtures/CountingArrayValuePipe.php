<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class CountingArrayValuePipe implements ValuePipe
{
    public static int $calls = 0;

    public function handle(#[\SensitiveParameter] mixed $value, string $paramName, callable $next): mixed
    {
        self::$calls++;

        return $next($value);
    }
}
