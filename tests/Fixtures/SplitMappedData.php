<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapInputName;
use StdOut\SimpleDataObjects\Attributes\MapOutputName;
use StdOut\SimpleDataObjects\BaseData;

class SplitMappedData extends BaseData
{
    public function __construct(
        #[MapInputName('legacy_id')]
        #[MapOutputName('id')]
        public readonly int $userId,
        public readonly string $name,
    ) {}
}
