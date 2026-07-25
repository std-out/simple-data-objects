<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class GenericChannelData extends ChannelData
{
    public function __construct(
        public readonly ?string $channel = null,
        public readonly ?string $payload = null,
    ) {}
}
