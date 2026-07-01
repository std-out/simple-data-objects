<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class ConflictCollectionFlattenData extends BaseData
{
    public function __construct(
        #[DataCollection(UserData::class)]
        #[Flatten]
        public readonly TypedDataCollection $items,
    ) {}
}
