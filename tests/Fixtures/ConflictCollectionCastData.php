<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\BooleanCast;
use StdOut\SimpleDataObjects\TypedDataCollection;

class ConflictCollectionCastData extends BaseData
{
    public function __construct(
        #[DataCollection(UserData::class)]
        #[Cast(new BooleanCast)]
        public readonly TypedDataCollection $items,
    ) {}
}
