<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Computed;
use StdOut\SimpleDataObjects\BaseData;

class ComputedNestedData extends BaseData
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}

    #[Computed]
    public function address(): AddressData
    {
        return new AddressData($this->street, $this->city);
    }
}
