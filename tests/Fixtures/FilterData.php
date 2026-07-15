<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\CommaSeparatedCast;

class FilterData extends BaseData
{
    public function __construct(
        #[Cast(new CommaSeparatedCast)]
        public readonly array $tags,

        #[Cast(new CommaSeparatedCast(separator: '|', trim: false))]
        public readonly array $raw,
    ) {}
}
