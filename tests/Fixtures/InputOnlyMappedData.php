<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapInputName;
use StdOut\SimpleDataObjects\BaseData;

class InputOnlyMappedData extends BaseData
{
    public function __construct(
        #[MapInputName('legacy_user_id', 'old_id')]
        public readonly int $userId,
    ) {}
}
