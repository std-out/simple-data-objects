<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class OtherThingData extends ThingData
{
    public function __construct(
        public readonly string $category,
        public readonly ?string $note = null,
    ) {}
}
