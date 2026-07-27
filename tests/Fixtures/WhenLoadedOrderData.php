<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\DataCollection;
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\Attributes\WhenLoaded;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;
use StdOut\SimpleDataObjects\TypedDataCollection;

class WhenLoadedOrderData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly int $id,
        #[WhenLoaded('customer')]
        #[MapPropertyName('customerData')]
        public readonly ?WhenLoadedCustomerData $client = null,
        #[WhenLoaded('items')]
        #[DataCollection(WhenLoadedItemData::class)]
        public readonly ?TypedDataCollection $items = null,
    ) {}
}
