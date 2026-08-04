<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;

class AliasedUserData extends BaseData
{
    public function __construct(
        #[MapPropertyName('user_id', 'userId', 'uid')]
        public readonly int $userId,
        public readonly string $name,
    ) {}
}
