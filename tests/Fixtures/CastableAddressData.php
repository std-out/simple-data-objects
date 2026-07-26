<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\Castable;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\IsEloquentCastable;

class CastableAddressData extends BaseData implements Castable
{
    use IsEloquentCastable;

    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}
