<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;

class NotDataObjectCollectionData extends BaseData
{
    public function __construct(
        #[DataCollection(\stdClass::class)]
        public readonly iterable $items,
    ) {}
}
