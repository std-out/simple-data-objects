<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Sensitive;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasSensitiveFields;

final class SensitiveCardData extends BaseData
{
    use HasSensitiveFields;

    private string $note = 'internal';

    public function __construct(
        public readonly string $holder,
        #[Sensitive]
        public readonly string $pan,
        #[Sensitive]
        public readonly Status $status = Status::Active,
        #[Sensitive]
        public readonly ?string $cvv = null,
    ) {}
}
