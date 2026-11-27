<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Pipes\TrimStringsPipe;

#[Pipe(TrimStringsPipe::class)]
final class ClassAndFlattenPipedData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'min:3'])]
        public readonly string $code,
        #[Flatten]
        #[Pipe(CountingArrayValuePipe::class)]
        public readonly UserData $user,
    ) {}
}
