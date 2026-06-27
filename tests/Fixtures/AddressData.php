<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class AddressData extends BaseData
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly ?string $zip = null,
    ) {}
}
