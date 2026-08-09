<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Optional;

class OptionalUnionOrderData extends BaseData
{
    public function __construct(
        public readonly Optional|Status $status,
    ) {}
}
