<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\BaseData;

#[InferRules]
class InferredAddressData extends BaseData
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly ?string $zip = null,
    ) {}
}
