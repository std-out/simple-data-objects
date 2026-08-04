<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapOutputName;
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;

class ConflictMapPropertyNameAndOutputData extends BaseData
{
    public function __construct(
        #[MapPropertyName('user_id')]
        #[MapOutputName('id')]
        public readonly int $userId,
    ) {}
}
