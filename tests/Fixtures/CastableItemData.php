<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class CastableItemData extends BaseData
{
    public function __construct(
        public readonly string $sku,
        public readonly int $quantity,
    ) {}
}
