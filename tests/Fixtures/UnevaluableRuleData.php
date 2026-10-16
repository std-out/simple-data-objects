<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

final class UnevaluableRuleData extends BaseData
{
    public function __construct(
        #[Rules(['unevaluable'])]
        public readonly string $name,
    ) {}
}
