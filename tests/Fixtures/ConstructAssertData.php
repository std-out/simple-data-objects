<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class ConstructAssertData extends BaseData
{
    public function __construct(
        public readonly int $low,
        public readonly int $high,
    ) {
        if ($low > $high) {
            throw new \InvalidArgumentException('low must not exceed high');
        }
    }
}
