<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class EmailChannelData extends ChannelData
{
    public function __construct(
        public readonly string $address,
        public readonly string $channel = 'email',
    ) {}
}
