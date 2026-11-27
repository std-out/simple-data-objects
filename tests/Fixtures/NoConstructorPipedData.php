<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Pipes\TrimStringsPipe;

#[Pipe(TrimStringsPipe::class)]
final class NoConstructorPipedData extends BaseData
{
    #[Rules(['required', 'min:3'])]
    public readonly string $name;
}
