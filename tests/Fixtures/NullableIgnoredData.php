<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\IgnoreIfNull;
use StdOut\SimpleDataObjects\BaseData;

class NullableIgnoredData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[IgnoreIfNull]
        public readonly ?string $optional = null,
    ) {}
}
