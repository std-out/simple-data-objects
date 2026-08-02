<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\BaseData;

#[InferRules]
class InferredUnionAndUntypedData extends BaseData
{
    public function __construct(
        public readonly int|string $identifier,
        // 3 members so PHP keeps this a real union instead of collapsing to ?DateTimeImmutable
        public readonly \DateTimeImmutable|\Countable|null $marker = null,
        public $misc = null,
    ) {}
}
