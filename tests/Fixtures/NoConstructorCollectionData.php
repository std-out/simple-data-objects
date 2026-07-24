<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class NoConstructorCollectionData extends BaseData
{
    public string $name;

    #[DataCollection(UserData::class)]
    public TypedDataCollection $members;
}
