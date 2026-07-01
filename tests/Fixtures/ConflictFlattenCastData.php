<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\BooleanCast;

class ConflictFlattenCastData extends BaseData
{
    public function __construct(
        #[Flatten]
        #[Cast(new BooleanCast)]
        public readonly UserData $user,
    ) {}
}
