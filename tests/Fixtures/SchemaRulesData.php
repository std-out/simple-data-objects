<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

class SchemaRulesData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'in:draft,published,archived'])]
        public readonly string $status,
        #[Rules(['required', 'url'])]
        public readonly string $website,
        #[Rules(['array', 'max:5', 'min:1'])]
        public readonly array $tags,
        #[Rules(['integer', 'max:150', 'min:0'])]
        public readonly int $age,
        public readonly float $score,
        #[Rules(['boolean', 'max:5'])]
        public readonly bool $flag,
    ) {}
}
