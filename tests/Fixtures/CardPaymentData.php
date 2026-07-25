<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Rules;

class CardPaymentData extends PaymentMethodData
{
    public function __construct(
        public readonly int $amount,
        #[Rules(['required', 'string', 'size:4'])]
        public readonly string $last4,
        public readonly PaymentType $type = PaymentType::Card,
    ) {}
}
