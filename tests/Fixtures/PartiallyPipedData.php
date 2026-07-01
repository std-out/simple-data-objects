<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Pipes\NullifyEmptyStringValuePipe;
use StdOut\SimpleDataObjects\Pipes\TrimValuePipe;

class PartiallyPipedData extends BaseData
{
    public function __construct(
        #[Pipe(TrimValuePipe::class)]
        public readonly string $name,

        public readonly string $email,

        #[Pipe(TrimValuePipe::class, NullifyEmptyStringValuePipe::class)]
        public readonly ?string $bio = null,
    ) {}
}
