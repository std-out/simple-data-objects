<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

#[InferRules]
class InferredCollectionData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[DataCollection(InferredItemData::class)]
        public readonly TypedDataCollection $items,
    ) {}
}
