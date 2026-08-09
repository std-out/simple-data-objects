<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\WhenLoaded;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;
use StdOut\SimpleDataObjects\Optional;

class WhenLoadedOptionalData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly int $id,
        #[WhenLoaded('customer')]
        public readonly WhenLoadedCustomerData|Optional $customer,
    ) {}
}
