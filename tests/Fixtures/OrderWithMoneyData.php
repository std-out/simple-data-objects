<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\MoneyCast;
use StdOut\SimpleDataObjects\ValueObjects\Money;

class OrderWithMoneyData extends BaseData
{
    public function __construct(
        #[Cast(new MoneyCast(currency: 'USD'))]
        public readonly Money $price,
    ) {}
}
