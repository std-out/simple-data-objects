<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapInputName;
use StdOut\SimpleDataObjects\Attributes\MapPropertyName;
use StdOut\SimpleDataObjects\BaseData;

class ConflictMapPropertyNameAndInputData extends BaseData
{
    public function __construct(
        #[MapPropertyName('user_id')]
        #[MapInputName('uid')]
        public readonly int $userId,
    ) {}
}
