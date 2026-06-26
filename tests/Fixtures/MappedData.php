<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;

class MappedData extends BaseData
{
    public function __construct(
        #[MapPropertyName('user_name')]
        public readonly string $userName,
        #[MapPropertyName('user_id')]
        public readonly int $userId,
    ) {}
}
