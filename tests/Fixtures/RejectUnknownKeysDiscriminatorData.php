<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Discriminator;
use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\BaseData;

#[RejectUnknownKeys]
#[Discriminator(field: 'type', map: ['a' => RejectUnknownKeysDiscriminatorChildData::class])]
abstract class RejectUnknownKeysDiscriminatorData extends BaseData {}
