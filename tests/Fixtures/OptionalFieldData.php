<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Optional;

class OptionalFieldData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string|Optional $name,
        public readonly string|Optional|null $bio,
    ) {}
}
