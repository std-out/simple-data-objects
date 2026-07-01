<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\BaseData;

class FlattenNonDtoData extends BaseData
{
    public function __construct(
        #[Flatten]
        public readonly string $name,
    ) {}
}
