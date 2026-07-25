<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Discriminator;

#[Discriminator(field: 'wheels', map: [
    2 => BikeData::class,
    4 => CarData::class,
])]
abstract class VehicleData extends ThingData {}
