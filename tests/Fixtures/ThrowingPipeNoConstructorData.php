<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;

#[Pipe(ThrowingPipe::class)]
class ThrowingPipeNoConstructorData extends BaseData
{
    public string $name;
}
