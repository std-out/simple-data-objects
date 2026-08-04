<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapOutputName;
use StdOut\SimpleDataObjects\BaseData;

class OutputOnlyMappedData extends BaseData
{
    public function __construct(
        #[MapOutputName('id')]
        public readonly int $userId,
    ) {}
}
