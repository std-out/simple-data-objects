<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

class LaravelUserData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
