<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\BaseData;

class ContextFlattenData extends BaseData
{
    public function __construct(
        public readonly string $label,
        #[Flatten]
        public readonly ContextHiddenData $detail,
    ) {}
}
