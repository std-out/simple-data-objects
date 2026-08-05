<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Computed;
use StdOut\SimpleDataObjects\BaseData;

class ComputedStaticMethodData extends BaseData
{
    public function __construct(
        public readonly string $name,
    ) {}

    #[Computed]
    public static function staticComputed(): string
    {
        return 'ignored';
    }
}
