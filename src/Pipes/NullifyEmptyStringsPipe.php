<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Pipes;

use StdOut\SimpleDataObjects\Contracts\DataPipe;

final class NullifyEmptyStringsPipe implements DataPipe
{
    public function handle(array $data, string $dataClass, callable $next): array
    {
        return $next(array_map(
            static fn (mixed $value): mixed => $value === '' ? null : $value,
            $data,
        ));
    }
}
