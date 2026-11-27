<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

final class NestedRulesTreeData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'min:3'])]
        public readonly string $name,
        public readonly ?NestedRulesTreeData $parent = null,
    ) {}
}
