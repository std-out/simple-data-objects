<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class InvalidCollectionData extends BaseData
{
    public function __construct(
        #[DataCollection('NonExistent\\Class\\ThatDoesNotExist')]
        public readonly TypedDataCollection $items,
    ) {}
}
