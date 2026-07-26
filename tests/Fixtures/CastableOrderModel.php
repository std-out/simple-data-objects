<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use StdOut\SimpleDataObjects\Laravel\AsDataCollection;

class CastableOrderModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'address' => CastableAddressData::class,
        'items' => AsDataCollection::class.':'.CastableItemData::class,
        'payment' => CastablePaymentData::class,
    ];
}
