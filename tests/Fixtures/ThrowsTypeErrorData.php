<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

final class ThrowsTypeErrorData extends BaseData
{
    public static int $calls = 0;

    public function __construct(public readonly int $count, public readonly float $ratio = 0.0)
    {
        self::$calls++;

        throw new \TypeError('Count rejected.');
    }
}
