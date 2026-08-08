<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class CollectingEdgeCasesData extends BaseData
{
    public function __construct(
        public readonly AddressData $required,
        public readonly ?AddressData $nullable,
        #[DataCollection(UserData::class)]
        public readonly TypedDataCollection $requiredItems,
        #[DataCollection(UserData::class)]
        public readonly ?TypedDataCollection $nullableItems,
        public readonly ?AddressData $defaulted = null,
        #[DataCollection(UserData::class)]
        public readonly ?TypedDataCollection $defaultedItems = null,
    ) {}
}
