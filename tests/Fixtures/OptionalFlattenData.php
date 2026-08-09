<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Optional;

class OptionalFlattenData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[Flatten]
        public readonly AddressData|Optional $address,
    ) {}
}
