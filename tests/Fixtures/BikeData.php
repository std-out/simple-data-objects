<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

class BikeData extends VehicleData
{
    public function __construct(
        public readonly string $category,
        public readonly int $wheels,
        public readonly bool $electric = false,
    ) {}
}
