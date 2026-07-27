<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\RejectUnknownKeys;
use StdOut\SimpleDataObjects\BaseData;

#[RejectUnknownKeys]
class StrictNoConstructorData extends BaseData
{
    public string $name;

    public ?string $email = null;
}
