<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Computed;
use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\BaseData;

#[RejectUnknownKeys]
class StrictComputedData extends BaseData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}

    #[Computed]
    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
