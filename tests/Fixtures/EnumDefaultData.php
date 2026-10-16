<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

final class EnumDefaultData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly array $statuses = [Status::Active],
        public readonly Status $status = Status::Active,
    ) {}
}
