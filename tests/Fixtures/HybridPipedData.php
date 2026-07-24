<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Pipes\NullifyEmptyStringsPipe;
use StdOut\SimpleDataObjects\Pipes\TrimStringsPipe;

#[Pipe(TrimStringsPipe::class, NullifyEmptyStringsPipe::class)]
class HybridPipedData extends BaseData
{
    public function __construct(
        public readonly string $name,
    ) {}

    public ?string $bio = null;
}
