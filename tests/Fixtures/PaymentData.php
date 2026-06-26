<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\EnumCast;

class PaymentData extends BaseData
{
    public function __construct(
        public readonly int $amount,
        #[Cast(new EnumCast(Status::class, Status::Inactive))]
        public readonly Status $status,
    ) {}
}
