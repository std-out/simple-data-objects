<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class PipedChildData extends PipedDiscriminatorData
{
    public function __construct(
        public readonly string $type,
    ) {}
}
