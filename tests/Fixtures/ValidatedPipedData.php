<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapInputName;
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Pipes\TrimStringsPipe;

#[Pipe(TrimStringsPipe::class)]
#[RejectUnknownKeys]
final class ValidatedPipedData extends BaseData
{
    /** @param list<string> $tags */
    public function __construct(
        #[Rules(['required', 'string', 'min:3'])]
        public readonly string $name,
        #[MapInputName('tags', 'labels')]
        #[Pipe(CommaSplitValuePipe::class)]
        #[Rules(['array', 'min:2'])]
        public readonly array $tags = [],
    ) {}
}
