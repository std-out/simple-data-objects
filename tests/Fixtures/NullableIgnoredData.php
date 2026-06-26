<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class NullableIgnoredData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $optional = null,
    ) {}

    protected function ignoreIfNull(): array
    {
        return ['optional'];
    }
}
