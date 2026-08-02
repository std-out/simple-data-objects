<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\WrapIn;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

#[WrapIn('data')]
class WrappedOrderData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly string $title,
    ) {}
}
