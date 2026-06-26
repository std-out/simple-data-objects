<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class OrderData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly Status $status,
        public readonly ?Status $previousStatus = null,
    ) {}
}
