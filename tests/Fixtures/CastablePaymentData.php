<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\Castable;
use StdOut\SimpleDataObjects\Attributes\Discriminator;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\IsEloquentCastable;

#[Discriminator(field: 'type', map: [
    'card' => CastableCardPaymentData::class,
    'bank' => CastableBankPaymentData::class,
])]
abstract class CastablePaymentData extends BaseData implements Castable
{
    use IsEloquentCastable;
}
