<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;

class AliasedNoConstructorData extends BaseData
{
    #[MapPropertyName('user_id', 'uid')]
    public int $userId;

    public string $name;
}
