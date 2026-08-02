<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

#[InferRules]
class InferredExplicitOverrideData extends BaseData
{
    public function __construct(
        #[Rules(['nullable', 'string'])]
        public readonly string $name,
    ) {}
}
