<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface DataPipe
{
    /** @param class-string $dataClass */
    public function handle(#[\SensitiveParameter] array $data, string $dataClass, callable $next): array;
}
