<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Optional;
use StdOut\SimpleDataObjects\TypedDataCollection;

class OptionalCollectionData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[DataCollection(UserData::class)]
        public readonly TypedDataCollection|Optional $members,
    ) {}
}
