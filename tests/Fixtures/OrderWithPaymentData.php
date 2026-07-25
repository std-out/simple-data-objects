<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

class OrderWithPaymentData extends BaseData
{
    public function __construct(
        public readonly string $id,
        public readonly PaymentMethodData $payment,
        #[DataCollection(PaymentMethodData::class)]
        public readonly ?TypedDataCollection $history = null,
    ) {}
}
