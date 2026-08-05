<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Computed;
use StdOut\SimpleDataObjects\BaseData;

class ComputedNoConstructorData extends BaseData
{
    public string $firstName;

    public string $lastName;

    #[Computed]
    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}
