<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Computed;
use StdOut\SimpleDataObjects\BaseData;

class ComputedRequiredParamData extends BaseData
{
    public function __construct(
        public readonly string $name,
    ) {}

    #[Computed]
    public function greet(string $greeting): string
    {
        return "{$greeting}, {$this->name}!";
    }
}
