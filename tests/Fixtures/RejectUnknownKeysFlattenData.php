<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\BaseData;

#[RejectUnknownKeys]
class RejectUnknownKeysFlattenData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[Flatten]
        public readonly AddressData $address,
    ) {}
}
