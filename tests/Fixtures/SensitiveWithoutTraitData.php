<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Sensitive;
use StdOut\SimpleDataObjects\BaseData;

final class SensitiveWithoutTraitData extends BaseData
{
    public function __construct(
        #[Sensitive]
        public readonly string $pan,
    ) {}
}
