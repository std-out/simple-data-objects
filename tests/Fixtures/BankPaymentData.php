<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class BankPaymentData extends PaymentMethodData
{
    public string $type = 'bank';

    public int $amount = 0;

    public string $iban = '';
}
