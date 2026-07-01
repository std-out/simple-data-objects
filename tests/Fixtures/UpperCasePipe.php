<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\DataPipe;

final class UpperCasePipe implements DataPipe
{
    public function handle(array $data, string $dataClass, callable $next): array
    {
        return $next(array_map(
            static fn (mixed $v): mixed => is_string($v) ? strtoupper($v) : $v,
            $data,
        ));
    }
}
