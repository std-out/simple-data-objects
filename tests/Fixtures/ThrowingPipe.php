<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\DataPipe;

final class ThrowingPipe implements DataPipe
{
    public function handle(array $data, string $dataClass, callable $next): array
    {
        throw new \RuntimeException('pipe exploded');
    }
}
