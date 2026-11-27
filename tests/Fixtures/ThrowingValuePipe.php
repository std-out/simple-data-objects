<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\ValuePipe;

final class ThrowingValuePipe implements ValuePipe
{
    public function handle(#[\SensitiveParameter] mixed $value, string $paramName, callable $next): mixed
    {
        throw new \RuntimeException('value pipe exploded');
    }
}
