<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class ContextCollectionData extends BaseData
{
    public function __construct(
        public readonly string $label,
        #[DataCollection(ContextHiddenData::class)]
        public readonly TypedDataCollection $items,
    ) {}
}
