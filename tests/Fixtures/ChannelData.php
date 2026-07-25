<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Discriminator;
use StdOut\SimpleDataObjects\BaseData;

#[Discriminator(
    field: 'channel',
    map: ['email' => EmailChannelData::class],
    fallback: GenericChannelData::class,
)]
abstract class ChannelData extends BaseData {}
