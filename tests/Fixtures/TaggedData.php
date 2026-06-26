<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class TaggedData extends BaseData
{
    public function __construct(
        public readonly array $tags,
    ) {}
}
