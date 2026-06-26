<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\BooleanCast;
use StdOut\SimpleDataObjects\Casts\FloatCast;
use StdOut\SimpleDataObjects\Casts\IntegerCast;
use StdOut\SimpleDataObjects\Casts\JsonCast;
use StdOut\SimpleDataObjects\Casts\TrimCast;

class ProductData extends BaseData
{
    public function __construct(
        #[Cast(new TrimCast(TrimCast::LOWERCASE))]
        public readonly string $sku,
        #[Cast(new IntegerCast)]
        public readonly int $quantity,
        #[Cast(new FloatCast(2))]
        public readonly float $price,
        #[Cast(new BooleanCast)]
        public readonly bool $available,
        #[Cast(new JsonCast)]
        public readonly array $meta,
    ) {}
}
