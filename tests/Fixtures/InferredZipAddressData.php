<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

#[InferRules]
final class InferredZipAddressData extends BaseData
{
    public function __construct(
        public readonly string $street,
        #[Rules(['required', 'string', 'max:5'])]
        public readonly string $zip,
    ) {}
}
