<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\BaseData;

class NoConstructorFlattenData extends BaseData
{
    public string $name;

    #[Flatten]
    public AddressData $address;
}
