<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class UnionTypeData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly ?Status $status = null,
    ) {}
}
