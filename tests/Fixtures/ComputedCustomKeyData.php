<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Computed;
use StdOut\SimpleDataObjects\BaseData;

class ComputedCustomKeyData extends BaseData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}

    #[Computed('full_name')]
    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
