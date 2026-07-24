<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class NoConstructorNestedData extends BaseData
{
    public string $name;

    public AddressData $address;
}
