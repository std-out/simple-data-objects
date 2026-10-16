<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Sensitive;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasSensitiveFields;

class SensitiveOptionalData extends BaseData
{
    use HasSensitiveFields;

    public function __construct(
        public readonly string $holder,
        #[Sensitive]
        public readonly ?string $cvv = null,
    ) {}
}
