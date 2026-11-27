<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

final class BoundedAmountData extends BaseData
{
    public function __construct(
        #[Rules(['numeric', 'max:100'])]
        public readonly int|float $amount,
    ) {}
}
