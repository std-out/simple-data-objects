<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

final class DistinctItemData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'distinct'])]
        public readonly string $sku,
    ) {}
}
