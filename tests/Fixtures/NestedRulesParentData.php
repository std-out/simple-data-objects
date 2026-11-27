<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Optional;
use StdOut\SimpleDataObjects\TypedDataCollection;

final class NestedRulesParentData extends BaseData
{
    public function __construct(
        public readonly NestedRulesNameData $required,
        public readonly NestedRulesNameData|Optional $optional,
        #[DataCollection(NestedRulesNameData::class)]
        public readonly TypedDataCollection $items,
        public readonly ?NestedRulesNameData $nullable = null,
        #[DataCollection(NestedRulesNameData::class)]
        public readonly ?TypedDataCollection $nullableItems = null,
        public readonly NestedRulesNameData $defaulted = new NestedRulesNameData('Alice'),
    ) {}
}
