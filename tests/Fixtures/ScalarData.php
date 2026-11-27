<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Attributes\MapInputName;
use StdOut\SimpleDataObjects\Attributes\Pipe;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\IntegerCast;
use StdOut\SimpleDataObjects\Pipes\TrimValuePipe;

final class ScalarData extends BaseData
{
    public function __construct(
        #[Cast(new IntegerCast)]
        public readonly int $casted = 0,
        public readonly int $int = 0,
        public readonly float $float = 0.0,
        public readonly string $string = '',
        public readonly bool $bool = false,
        public readonly ?int $nullableInt = null,
        #[MapInputName('flag', 'is_flag')]
        public readonly ?bool $flag = null,
        #[Pipe(TrimValuePipe::class)]
        public readonly ?int $piped = null,
        public readonly int|string $union = 0,
    ) {}
}
