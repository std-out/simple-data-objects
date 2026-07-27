<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class RejectUnknownKeysDiscriminatorChildData extends RejectUnknownKeysDiscriminatorData
{
    public function __construct(
        public readonly string $type = 'a',
    ) {}
}
