<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

final class ScalarPropertiesData extends BaseData
{
    public int $count;

    public ?float $ratio;
}
