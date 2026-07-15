<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\UuidCast;

class OrderWithUuidData extends BaseData
{
    public function __construct(
        #[Cast(new UuidCast)]
        public readonly string $id,
    ) {}
}
