<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;

class NonExportableData extends BaseData
{
    public function __construct(
        #[Cast(new NonExportableCast)]
        public readonly string $value,
    ) {}
}
