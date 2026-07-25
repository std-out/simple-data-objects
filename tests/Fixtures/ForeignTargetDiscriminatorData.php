<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Discriminator;
use StdOut\SimpleDataObjects\BaseData;

#[Discriminator(field: 'type', map: ['x' => AddressData::class])]
abstract class ForeignTargetDiscriminatorData extends BaseData {}
