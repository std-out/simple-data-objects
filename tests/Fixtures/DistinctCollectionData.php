<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

#[InferRules]
final class DistinctCollectionData extends BaseData
{
    public function __construct(
        #[DataCollection(DistinctItemData::class)]
        public readonly TypedDataCollection $items,
    ) {}
}
