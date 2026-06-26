<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

class ProfileData extends BaseData
{
    public function __construct(
        public readonly UserData $user,
        public readonly string $bio,
    ) {}
}
