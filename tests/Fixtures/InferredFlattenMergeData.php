<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Flatten;
use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

#[InferRules]
final class InferredFlattenMergeData extends BaseData
{
    public function __construct(
        public readonly string $name,
        #[Flatten]
        #[Rules(['array'], merge: true)]
        public readonly InferredZipAddressData $address,
    ) {}
}
