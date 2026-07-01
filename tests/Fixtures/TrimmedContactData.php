<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Pipes\TrimStringsPipe;

#[Pipe(TrimStringsPipe::class)]
class TrimmedContactData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
