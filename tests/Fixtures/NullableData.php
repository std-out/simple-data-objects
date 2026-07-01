<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class NullableData extends BaseData
{
    public function __construct(
        public readonly string $required,
        public readonly ?string $optional,
    ) {}
}
