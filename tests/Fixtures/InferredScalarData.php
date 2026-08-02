<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\BaseData;

#[InferRules]
class InferredScalarData extends BaseData
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly float $balance,
        public readonly bool $active,
        public readonly array $tags,
        public readonly ?string $nickname = null,
    ) {}
}
