<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class CastableBankPaymentData extends CastablePaymentData
{
    public function __construct(
        public readonly string $iban,
        public readonly string $type = 'bank',
    ) {}
}
