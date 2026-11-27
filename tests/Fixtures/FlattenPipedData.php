<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;

final class FlattenPipedData extends BaseData
{
    public function __construct(
        #[Flatten]
        #[Pipe(CountingArrayValuePipe::class)]
        public readonly UserData $user,
    ) {}
}
