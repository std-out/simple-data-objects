<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\InferRules;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\TypedDataCollection;

#[InferRules]
class InferredCheckoutData extends BaseData
{
    public function __construct(
        public readonly PaymentMethodData $payment,
        #[DataCollection(PaymentMethodData::class)]
        public readonly TypedDataCollection $refunds,
        public readonly ?InferredAliasedContactData $contact = null,
    ) {}
}
