<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

final class ThrowingValuePipeData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string'])]
        public readonly string $name,
        #[Pipe(ThrowingValuePipe::class)]
        public readonly ?string $nickname = null,
    ) {}
}
