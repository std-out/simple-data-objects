<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Discriminator;
use StdOut\SimpleDataObjects\BaseData;

#[Discriminator(field: 'category', map: [
    'vehicle' => VehicleData::class,
    'other' => OtherThingData::class,
])]
abstract class ThingData extends BaseData {}
