<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Pipes;

use StdOut\SimpleDataObjects\Contracts\DataPipe;

final class TrimStringsPipe implements DataPipe
{
    public function handle(array $data, string $dataClass, callable $next): array
    {
        return $next(array_map(
            static fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
            $data,
        ));
    }
}
