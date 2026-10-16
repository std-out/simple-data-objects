<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Sensitive;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasSensitiveFields;

final class SensitivePrivateData extends BaseData
{
    use HasSensitiveFields;

    public function __construct(
        public readonly string $holder,
        #[Sensitive]
        private readonly string $pin,
    ) {}

    public function pin(): string
    {
        return $this->pin;
    }
}
